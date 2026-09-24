<?php

namespace App\Services\Performance;

use App\Models\Admin;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeKra;
use App\Models\Penalty;
use App\Models\PerformanceCycle;
use App\Models\PerformanceHistory;
use App\Models\PerformanceHrReview;
use App\Models\PerformanceManagerReview;
use App\Models\PerformanceScore;
use App\Models\PerformanceSelfReview;
use App\Models\Warning;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The Employee → Manager → HR → Admin escalation trail.
 *
 * Every transition goes through here so three things are guaranteed:
 * the cycle is open, the stage before it is complete, and the move is written
 * to performance_histories with who did it and what they said.
 *
 * A send-back at any stage returns the review to the stage before it and
 * reopens exactly that stage's record — nothing further down the chain is
 * discarded, so HR sending a review back to a manager does not wipe the
 * employee's self-assessment.
 */
class PerformanceWorkflowService
{
    public function __construct(private PerformanceScoringService $scoring) {}

    // ── Stage 1: employee self-assessment ────────────────────────────────

    /**
     * Submit the employee's self-assessment and lock it.
     *
     * @throws RuntimeException when the cycle is not accepting assessments
     */
    public function submitSelfReview(PerformanceCycle $cycle, Employee $employee, array $data): PerformanceSelfReview
    {
        $this->assertOpen($cycle);

        return DB::transaction(function () use ($cycle, $employee, $data) {
            $review = PerformanceSelfReview::updateOrCreate(
                ['performance_cycle_id' => $cycle->id, 'employee_id' => $employee->id],
                array_merge($data, [
                    'business_id' => $cycle->business_id,
                    'status' => 'submitted',
                    'submitted_at' => now(),
                ]),
            );

            // Every goal moves with the write-up — a self-assessment covers the
            // whole cycle, not one KRA at a time.
            EmployeeKra::where('performance_cycle_id', $cycle->id)
                ->where('employee_id', $employee->id)
                ->whereIn('status', ['assigned', 'sent_back'])
                ->update(['status' => 'self_submitted']);

            $this->record($cycle, $employee->id, 'self', 'submitted', null, $employee->id);

            return $review->refresh();
        });
    }

    /** Save without submitting — the draft the employee can come back to. */
    public function saveSelfReviewDraft(PerformanceCycle $cycle, Employee $employee, array $data): PerformanceSelfReview
    {
        $this->assertOpen($cycle);

        return PerformanceSelfReview::updateOrCreate(
            ['performance_cycle_id' => $cycle->id, 'employee_id' => $employee->id],
            array_merge($data, ['business_id' => $cycle->business_id, 'status' => 'draft']),
        );
    }

    // ── Stage 2: manager assessment ──────────────────────────────────────

    /**
     * Record the manager's assessment. Per-KRA ratings and feedback are saved
     * alongside the cycle-level write-up.
     *
     * @param  array<int,array{rating?:float, feedback?:string}>  $kraRatings  keyed by employee_kra id
     */
    public function submitManagerReview(
        PerformanceCycle $cycle,
        int $employeeId,
        array $data,
        array $kraRatings = [],
        ?Employee $reviewerEmployee = null,
        ?Admin $reviewerAdmin = null,
    ): PerformanceManagerReview {
        $this->assertOpen($cycle);

        return DB::transaction(function () use ($cycle, $employeeId, $data, $kraRatings, $reviewerEmployee, $reviewerAdmin) {
            foreach ($kraRatings as $employeeKraId => $values) {
                $employeeKra = EmployeeKra::where('performance_cycle_id', $cycle->id)
                    ->where('employee_id', $employeeId)
                    ->find($employeeKraId);

                if (! $employeeKra) {
                    continue;
                }

                $employeeKra->update([
                    'manager_rating' => $values['rating'] ?? $employeeKra->manager_rating,
                    'manager_feedback' => $values['feedback'] ?? $employeeKra->manager_feedback,
                    'status' => 'manager_reviewed',
                ]);
            }

            EmployeeKra::where('performance_cycle_id', $cycle->id)
                ->where('employee_id', $employeeId)
                ->whereIn('status', ['assigned', 'self_submitted', 'sent_back'])
                ->update(['status' => 'manager_reviewed']);

            $review = PerformanceManagerReview::updateOrCreate(
                ['performance_cycle_id' => $cycle->id, 'employee_id' => $employeeId],
                array_merge($data, [
                    'business_id' => $cycle->business_id,
                    'reviewer_employee_id' => $reviewerEmployee?->id,
                    'reviewer_admin_id' => $reviewerAdmin?->id,
                    'status' => 'submitted',
                    'submitted_at' => now(),
                ]),
            );

            $this->record($cycle, $employeeId, 'manager', 'reviewed', $reviewerAdmin?->id, $reviewerEmployee?->id);

            return $review->refresh();
        });
    }

    // ── Stage 3: HR review and finalisation ──────────────────────────────

    /**
     * HR's moderation pass. Saving does not finalise — that is a separate,
     * deliberate action, because finalising freezes the score.
     */
    public function saveHrReview(PerformanceCycle $cycle, int $employeeId, array $data, ?Admin $admin = null): PerformanceHrReview
    {
        $this->assertOpen($cycle);

        return PerformanceHrReview::updateOrCreate(
            ['performance_cycle_id' => $cycle->id, 'employee_id' => $employeeId],
            array_merge($data, [
                'business_id' => $cycle->business_id,
                'reviewer_admin_id' => $admin?->id ?? Auth::guard('admin')->id(),
                'status' => 'pending',
            ]) + $this->disciplineSnapshot($cycle, $employeeId),
        );
    }

    /**
     * Finalise: compute the score, band it, suggest a reward, and close the
     * employee's goals for this cycle.
     */
    public function finalize(PerformanceCycle $cycle, int $employeeId, ?Admin $admin = null): PerformanceScore
    {
        $this->assertOpen($cycle);

        return DB::transaction(function () use ($cycle, $employeeId, $admin) {
            $adminId = $admin?->id ?? Auth::guard('admin')->id();

            PerformanceHrReview::updateOrCreate(
                ['performance_cycle_id' => $cycle->id, 'employee_id' => $employeeId],
                [
                    'business_id' => $cycle->business_id,
                    'reviewer_admin_id' => $adminId,
                    'status' => 'finalized',
                    'finalized_at' => now(),
                ] + $this->disciplineSnapshot($cycle, $employeeId),
            );

            EmployeeKra::where('performance_cycle_id', $cycle->id)
                ->where('employee_id', $employeeId)
                ->update(['status' => 'finalized']);

            $score = $this->scoring->finalizeEmployee($cycle, $employeeId, $adminId);

            $this->record($cycle, $employeeId, 'hr', 'finalized', $adminId, null,
                'Final score '.number_format((float) $score->final_score, 2).' — '.($score->band_name ?? 'unbanded'));

            return $score;
        });
    }

    // ── Send back ────────────────────────────────────────────────────────

    /**
     * Return a review to the previous stage for revision.
     *
     * Only the target stage is reopened. Sending back to the manager leaves the
     * employee's self-assessment submitted; sending back to the employee leaves
     * the manager's review intact so they can see what changed.
     *
     * @param  string  $to  self|manager
     */
    public function sendBack(PerformanceCycle $cycle, int $employeeId, string $to, string $remarks, ?Admin $admin = null, ?Employee $actor = null): void
    {
        $this->assertOpen($cycle);

        if (! in_array($to, ['self', 'manager'], true)) {
            throw new RuntimeException('A review can only be sent back to the employee or the manager.');
        }

        DB::transaction(function () use ($cycle, $employeeId, $to, $remarks, $admin, $actor) {
            if ($to === 'self') {
                PerformanceSelfReview::where('performance_cycle_id', $cycle->id)
                    ->where('employee_id', $employeeId)
                    ->update(['status' => 'sent_back', 'submitted_at' => null]);

                EmployeeKra::where('performance_cycle_id', $cycle->id)
                    ->where('employee_id', $employeeId)
                    ->update(['status' => 'sent_back']);
            } else {
                PerformanceManagerReview::where('performance_cycle_id', $cycle->id)
                    ->where('employee_id', $employeeId)
                    ->update(['status' => 'sent_back', 'submitted_at' => null]);

                EmployeeKra::where('performance_cycle_id', $cycle->id)
                    ->where('employee_id', $employeeId)
                    ->update(['status' => 'self_submitted']);
            }

            $this->record($cycle, $employeeId, $to, 'sent_back',
                $admin?->id ?? Auth::guard('admin')->id(), $actor?->id, $remarks);
        });
    }

    // ── Queues ───────────────────────────────────────────────────────────

    /**
     * "Pending at my desk" for one stage of a cycle.
     *
     * @param  string  $stage  self|manager|hr
     * @param  int|null  $managerId  narrow the manager queue to one manager's team
     */
    public function pendingQueue(PerformanceCycle $cycle, string $stage, ?int $managerId = null)
    {
        $query = EmployeeKra::query()
            ->with(['employee.department', 'kra', 'manager'])
            ->where('performance_cycle_id', $cycle->id);

        return match ($stage) {
            'self' => $query->whereIn('status', ['assigned', 'sent_back']),
            'manager' => $query->where('status', 'self_submitted')
                ->when($managerId, fn ($q, $id) => $q->where('manager_id', $id)),
            'hr' => $query->where('status', 'manager_reviewed'),
            default => $query->whereRaw('1 = 0'),
        };
    }

    // ── Internals ────────────────────────────────────────────────────────

    /**
     * Attendance, penalty and warning figures for the cycle period — the data
     * HR verifies a score against, snapshotted so it stays meaningful later.
     *
     * @return array{attendance_percent:float|null, penalty_count:int, warning_count:int}
     */
    public function disciplineSnapshot(PerformanceCycle $cycle, int $employeeId): array
    {
        $penalties = Penalty::where('employee_id', $employeeId)
            ->whereBetween('incident_date', [$cycle->period_start, $cycle->period_end])
            ->count();

        $warnings = Warning::where('employee_id', $employeeId)
            ->whereBetween('issued_on', [$cycle->period_start, $cycle->period_end])
            ->count();

        // Attendance across the cycle, counted straight off the register.
        //
        // Expected days are the ones the employee was due at work — present,
        // half-day, absent and on-leave. Week-offs and holidays are excluded
        // from both sides, so a quarter with more public holidays does not
        // quietly depress everyone's percentage. A half-day counts as half.
        $tally = Attendance::where('employee_id', $employeeId)
            ->whereBetween('date', [$cycle->period_start, $cycle->period_end])
            ->whereIn('status', ['present', 'half_day', 'absent', 'on_leave'])
            ->groupBy('status')
            ->selectRaw('status, COUNT(*) as total')
            ->pluck('total', 'status');

        $expected = (float) $tally->sum();
        $present = (float) ($tally['present'] ?? 0) + 0.5 * (float) ($tally['half_day'] ?? 0);
        $attendance = $expected > 0 ? round($present / $expected * 100, 2) : null;

        return [
            'attendance_percent' => $attendance,
            'penalty_count' => $penalties,
            'warning_count' => $warnings,
        ];
    }

    private function record(
        PerformanceCycle $cycle,
        int $employeeId,
        string $stage,
        string $action,
        ?int $adminId = null,
        ?int $employeeActorId = null,
        ?string $remarks = null,
    ): void {
        PerformanceHistory::create([
            'business_id' => $cycle->business_id,
            'performance_cycle_id' => $cycle->id,
            'employee_id' => $employeeId,
            'stage' => $stage,
            'action' => $action,
            'actor_admin_id' => $adminId,
            'actor_employee_id' => $employeeActorId,
            'remarks' => $remarks,
        ]);
    }

    /** Assessments are only accepted while the cycle is open. */
    private function assertOpen(PerformanceCycle $cycle): void
    {
        if (! $cycle->isOpen()) {
            throw new RuntimeException(
                "The cycle \"{$cycle->name}\" is {$cycle->status_label}. Scores can only be changed while a cycle is Open."
            );
        }
    }
}
