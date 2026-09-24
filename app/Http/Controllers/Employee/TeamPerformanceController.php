<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeKra;
use App\Models\PerformanceCycle;
use App\Models\PerformanceManagerReview;
use App\Models\PerformanceSelfReview;
use App\Notifications\NotificationDispatcher;
use App\Services\Performance\PerformanceScoringService;
use App\Services\Performance\PerformanceWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

/**
 * The manager's desk in the employee portal — the "Manager Assessment" stage.
 *
 * A manager here is whoever is named on the assigned goal (falling back to the
 * employee's reporting manager at assignment time), so a department head
 * reviews exactly the goals routed to them and nothing else.
 */
class TeamPerformanceController extends Controller
{
    public function __construct(
        private PerformanceWorkflowService $workflow,
        private PerformanceScoringService $scoring,
    ) {}

    /** Everything waiting for my review this cycle. */
    public function index(Request $request)
    {
        $manager = Auth::guard('employee')->user();
        $cycle = $this->resolveCycle($request, $manager);

        if (! $cycle) {
            return view('employee.team-performance.index', [
                'cycle' => null, 'cycles' => collect(), 'pending' => collect(), 'reviewed' => collect(),
            ]);
        }

        $goals = EmployeeKra::with(['employee.department', 'kra'])
            ->where('performance_cycle_id', $cycle->id)
            ->where('manager_id', $manager->id)
            ->get()
            ->groupBy('employee_id');

        return view('employee.team-performance.index', [
            'cycle' => $cycle,
            'cycles' => $this->managedCycles($manager),
            // Waiting on me: the employee has submitted, I have not reviewed.
            'pending' => $goals->filter(fn ($rows) => $rows->contains(fn ($r) => $r->status === 'self_submitted')),
            'reviewed' => $goals->filter(fn ($rows) => $rows->every(fn ($r) => in_array($r->status, ['manager_reviewed', 'hr_reviewed', 'finalized'], true))),
            'notStarted' => $goals->filter(fn ($rows) => $rows->every(fn ($r) => in_array($r->status, ['assigned', 'sent_back'], true))),
        ]);
    }

    /** Review one team member. */
    public function show(PerformanceCycle $cycle, Employee $employee)
    {
        $manager = Auth::guard('employee')->user();

        $goals = EmployeeKra::with(['kra', 'kpis.kpi', 'documents'])
            ->where('performance_cycle_id', $cycle->id)
            ->where('employee_id', $employee->id)
            ->where('manager_id', $manager->id)
            ->get();

        abort_if($goals->isEmpty(), 403, 'This employee does not report to you for this cycle.');

        return view('employee.team-performance.review', [
            'cycle' => $cycle,
            'employee' => $employee->load('department', 'designation'),
            'goals' => $goals,
            'selfReview' => PerformanceSelfReview::where('performance_cycle_id', $cycle->id)
                ->where('employee_id', $employee->id)->first(),
            'review' => PerformanceManagerReview::firstOrNew([
                'performance_cycle_id' => $cycle->id,
                'employee_id' => $employee->id,
            ]),
            'computed' => $this->scoring->computeForEmployee($cycle, $employee->id),
        ]);
    }

    /** Submit the manager assessment. */
    public function store(Request $request, PerformanceCycle $cycle, Employee $employee)
    {
        $manager = Auth::guard('employee')->user();

        abort_unless(
            EmployeeKra::where('performance_cycle_id', $cycle->id)
                ->where('employee_id', $employee->id)
                ->where('manager_id', $manager->id)
                ->exists(),
            403,
        );

        $data = $request->validate([
            'overall_rating' => ['required', 'numeric', 'min:1', 'max:5'],
            'feedback' => ['required', 'string', 'min:5', 'max:5000'],
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
        ], [
            'feedback.required' => 'Written feedback is required — the employee sees this.',
        ]);

        try {
            $this->saveAchieved($cycle, $employee->id, $manager->id, $data['kpi'] ?? []);

            $this->workflow->submitManagerReview(
                $cycle,
                $employee->id,
                [
                    'overall_rating' => $data['overall_rating'],
                    'feedback' => $data['feedback'],
                    'suggestions' => $data['suggestions'] ?? null,
                    'comments' => $data['comments'] ?? null,
                    'recommend_promotion' => $request->boolean('recommend_promotion'),
                    'recommend_training' => $request->boolean('recommend_training'),
                    'training_notes' => $data['training_notes'] ?? null,
                ],
                $data['kra'] ?? [],
                $manager,
            );
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        NotificationDispatcher::fire(
            'performance.manager_feedback',
            $employee,
            ['cycle' => $cycle->name],
        );

        if ($request->boolean('recommend_promotion')) {
            NotificationDispatcher::fire(
                'performance.promotion_recommended',
                $employee,
                ['cycle' => $cycle->name],
            );
        }

        return redirect()->route('employee.team-performance.index', ['cycle' => $cycle->id])
            ->with('success', "Review submitted for {$employee->full_name}. It is now with HR.");
    }

    /** Send the self-assessment back to the employee for revision. */
    public function sendBack(Request $request, PerformanceCycle $cycle, Employee $employee)
    {
        $manager = Auth::guard('employee')->user();

        abort_unless(
            EmployeeKra::where('performance_cycle_id', $cycle->id)
                ->where('employee_id', $employee->id)
                ->where('manager_id', $manager->id)
                ->exists(),
            403,
        );

        $data = $request->validate([
            'remarks' => ['required', 'string', 'min:3', 'max:500'],
        ], [
            'remarks.required' => 'Say what needs changing — the employee needs to know.',
        ]);

        try {
            $this->workflow->sendBack($cycle, $employee->id, 'self', $data['remarks'], null, $manager);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('employee.team-performance.index', ['cycle' => $cycle->id])
            ->with('success', 'Sent back to the employee for revision.');
    }

    /** @param array<int,mixed> $values employee_kpi id => achieved value */
    private function saveAchieved(PerformanceCycle $cycle, int $employeeId, int $managerId, array $values): void
    {
        if (empty($values)) {
            return;
        }

        $goals = EmployeeKra::with('kpis')
            ->where('performance_cycle_id', $cycle->id)
            ->where('employee_id', $employeeId)
            ->where('manager_id', $managerId)
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

    /** Cycles in which this manager has anything to review. */
    private function managedCycles(Employee $manager)
    {
        return PerformanceCycle::whereIn(
            'id',
            EmployeeKra::where('manager_id', $manager->id)->distinct()->pluck('performance_cycle_id'),
        )->orderByDesc('period_start')->get();
    }

    private function resolveCycle(Request $request, Employee $manager): ?PerformanceCycle
    {
        $mine = EmployeeKra::where('manager_id', $manager->id)->distinct()->pluck('performance_cycle_id');

        if ($request->filled('cycle')) {
            return PerformanceCycle::whereIn('id', $mine)->find($request->input('cycle'));
        }

        return PerformanceCycle::whereIn('id', $mine)->open()->orderByDesc('period_start')->first()
            ?? PerformanceCycle::whereIn('id', $mine)->orderByDesc('period_start')->first();
    }
}
