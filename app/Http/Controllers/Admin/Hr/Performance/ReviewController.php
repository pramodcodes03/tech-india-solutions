<?php

namespace App\Http\Controllers\Admin\Hr\Performance;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeKra;
use App\Models\PerformanceCycle;
use App\Models\PerformanceHistory;
use App\Models\PerformanceHrReview;
use App\Models\PerformanceManagerReview;
use App\Models\PerformanceScore;
use App\Models\PerformanceSelfReview;
use App\Notifications\NotificationDispatcher;
use App\Services\Performance\PerformanceScoringService;
use App\Services\Performance\PerformanceWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

/**
 * The review desk: the pending queue for each stage, the per-employee review
 * screen, and the manager / HR / finalise actions on it.
 */
class ReviewController extends Controller
{
    public function __construct(
        private PerformanceWorkflowService $workflow,
        private PerformanceScoringService $scoring,
    ) {}

    private function gate(string $action): void
    {
        abort_unless(Auth::guard('admin')->user()->can("performance_reviews.{$action}"), 403);
    }

    /** "Pending at my desk" for every stage of a cycle. */
    public function index(Request $request)
    {
        $this->gate('view');

        $cycle = $this->resolveCycle($request);

        if (! $cycle) {
            return view('admin.hr.performance.reviews.index', [
                'cycle' => null, 'cycles' => collect(), 'queues' => [], 'stage' => 'hr',
            ]);
        }

        $stage = $request->input('stage', 'hr');
        if (! in_array($stage, ['self', 'manager', 'hr'], true)) {
            $stage = 'hr';
        }

        // Counts for the tabs, and the rows for whichever tab is showing.
        $queues = [];
        foreach (['self', 'manager', 'hr'] as $s) {
            $queues[$s] = $this->workflow->pendingQueue($cycle, $s)->distinct('employee_id')->count('employee_id');
        }

        $rows = $this->workflow->pendingQueue($cycle, $stage)
            ->get()
            ->groupBy('employee_id');

        return view('admin.hr.performance.reviews.index', [
            'cycle' => $cycle,
            'cycles' => PerformanceCycle::orderByDesc('period_start')->get(),
            'queues' => $queues,
            'stage' => $stage,
            'rows' => $rows,
        ]);
    }

    /** One employee's full review for a cycle — every stage on one page. */
    public function show(Request $request, PerformanceCycle $cycle, Employee $employee)
    {
        $this->gate('view');

        $goals = EmployeeKra::with(['kra', 'kpis.kpi', 'manager', 'documents'])
            ->where('performance_cycle_id', $cycle->id)
            ->where('employee_id', $employee->id)
            ->get();

        abort_if($goals->isEmpty(), 404, 'This employee has no goals in this cycle.');

        $computed = $this->scoring->computeForEmployee($cycle, $employee->id);

        return view('admin.hr.performance.reviews.show', [
            'cycle' => $cycle,
            'employee' => $employee->load('department', 'designation', 'reportingManager'),
            'goals' => $goals,
            'selfReview' => PerformanceSelfReview::where('performance_cycle_id', $cycle->id)->where('employee_id', $employee->id)->first(),
            'managerReview' => PerformanceManagerReview::where('performance_cycle_id', $cycle->id)->where('employee_id', $employee->id)->first(),
            'hrReview' => PerformanceHrReview::where('performance_cycle_id', $cycle->id)->where('employee_id', $employee->id)->first(),
            'score' => PerformanceScore::with('band')->where('performance_cycle_id', $cycle->id)->where('employee_id', $employee->id)->first(),
            'computed' => $computed,
            'discipline' => $this->workflow->disciplineSnapshot($cycle, $employee->id),
            'history' => PerformanceHistory::with('actorAdmin', 'actorEmployee')
                ->where('performance_cycle_id', $cycle->id)
                ->where('employee_id', $employee->id)
                ->latest()->get(),
        ]);
    }

    /** Manager assessment submitted by an admin standing in for the manager. */
    public function managerReview(Request $request, PerformanceCycle $cycle, Employee $employee)
    {
        $this->gate('manager_review');

        $data = $request->validate([
            'overall_rating' => ['required', 'numeric', 'min:1', 'max:5'],
            'feedback' => ['nullable', 'string', 'max:5000'],
            'suggestions' => ['nullable', 'string', 'max:5000'],
            'comments' => ['nullable', 'string', 'max:5000'],
            'recommend_promotion' => ['nullable', 'boolean'],
            'recommend_training' => ['nullable', 'boolean'],
            'training_notes' => ['nullable', 'string', 'max:500'],
            'kra' => ['nullable', 'array'],
            'kra.*.rating' => ['nullable', 'numeric', 'min:1', 'max:5'],
            'kra.*.feedback' => ['nullable', 'string', 'max:2000'],
            'kpi' => ['nullable', 'array'],
            'kpi.*' => ['nullable', 'numeric'],
        ]);

        try {
            // Achieved values come in with the manager's review, because the
            // manager is the one who knows what was actually delivered.
            $this->saveAchievedValues($cycle, $employee, $data['kpi'] ?? []);

            $this->workflow->submitManagerReview(
                $cycle,
                $employee->id,
                [
                    'overall_rating' => $data['overall_rating'],
                    'feedback' => $data['feedback'] ?? null,
                    'suggestions' => $data['suggestions'] ?? null,
                    'comments' => $data['comments'] ?? null,
                    'recommend_promotion' => $request->boolean('recommend_promotion'),
                    'recommend_training' => $request->boolean('recommend_training'),
                    'training_notes' => $data['training_notes'] ?? null,
                ],
                $data['kra'] ?? [],
                null,
                Auth::guard('admin')->user(),
            );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        NotificationDispatcher::fire(
            'performance.manager_feedback',
            $employee,
            ['cycle' => $cycle->name],
        );

        return back()->with('success', 'Manager assessment saved. It is now with HR.');
    }

    /** HR moderation — saved without finalising. */
    public function hrReview(Request $request, PerformanceCycle $cycle, Employee $employee)
    {
        $this->gate('hr_review');

        $data = $request->validate([
            'moderated_rating' => ['nullable', 'numeric', 'min:1', 'max:5'],
            'moderation_reason' => ['nullable', 'string', 'max:500', 'required_with:moderated_rating'],
            'comments' => ['nullable', 'string', 'max:5000'],
        ], [
            'moderation_reason.required_with' => 'Give a reason when you moderate a manager rating — it is part of the audit trail.',
        ]);

        try {
            $this->workflow->saveHrReview($cycle, $employee->id, $data, Auth::guard('admin')->user());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'HR review saved. Finalise when you are ready to lock the score.');
    }

    /** Finalise: compute, band, recommend a reward, close the goals. */
    public function finalize(Request $request, PerformanceCycle $cycle, Employee $employee)
    {
        $this->gate('finalize');

        try {
            $score = $this->workflow->finalize($cycle, $employee->id, Auth::guard('admin')->user());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        NotificationDispatcher::fire(
            'performance.appraisal_generated',
            $employee,
            ['cycle' => $cycle->name, 'score' => (float) $score->final_score, 'band' => $score->band_name],
        );

        return back()->with('success', sprintf(
            'Finalised — %s scored %s (%s). Recommended: %s.',
            $employee->full_name,
            number_format((float) $score->final_score, 2),
            $score->band_name ?? 'unbanded',
            $score->recommendation_label,
        ));
    }

    /** Send the review back a stage for revision. */
    public function sendBack(Request $request, PerformanceCycle $cycle, Employee $employee)
    {
        $this->gate('send_back');

        $data = $request->validate([
            'to' => ['required', 'in:self,manager'],
            'remarks' => ['required', 'string', 'min:3', 'max:500'],
        ], [
            'remarks.required' => 'Say what needs changing — the person receiving it needs to know.',
        ]);

        try {
            $this->workflow->sendBack($cycle, $employee->id, $data['to'], $data['remarks'], Auth::guard('admin')->user());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $target = $data['to'] === 'self' ? 'the employee' : 'the manager';

        return back()->with('success', "Sent back to {$target} for revision.");
    }

    /**
     * Save achieved values against the assigned KPIs and rescore them.
     *
     * @param  array<int,mixed>  $values  employee_kpi id => achieved value
     */
    private function saveAchievedValues(PerformanceCycle $cycle, Employee $employee, array $values): void
    {
        if (empty($values)) {
            return;
        }

        $goals = EmployeeKra::with('kpis')
            ->where('performance_cycle_id', $cycle->id)
            ->where('employee_id', $employee->id)
            ->get();

        foreach ($goals as $goal) {
            $touched = false;

            foreach ($goal->kpis as $kpi) {
                if (! array_key_exists($kpi->id, $values)) {
                    continue;
                }

                $raw = $values[$kpi->id];
                $kpi->achieved_value = ($raw === null || $raw === '') ? null : (float) $raw;
                $kpi->recomputeScore();
                $kpi->save();
                $touched = true;
            }

            if ($touched) {
                $this->scoring->scoreKra($goal->fresh('kpis'));
            }
        }
    }

    private function resolveCycle(Request $request): ?PerformanceCycle
    {
        if ($request->filled('cycle')) {
            return PerformanceCycle::find($request->input('cycle'));
        }

        return PerformanceCycle::open()->orderByDesc('period_start')->first()
            ?? PerformanceCycle::orderByDesc('period_start')->first();
    }
}
