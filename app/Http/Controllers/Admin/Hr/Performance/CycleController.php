<?php

namespace App\Http\Controllers\Admin\Hr\Performance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Performance\StoreCycleRequest;
use App\Models\EmployeeKra;
use App\Models\PerformanceCycle;
use App\Models\PerformanceScore;
use App\Services\Performance\PerformanceScoringService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Performance cycles: the review periods everything else hangs off, plus the
 * open / lock control that freezes scores.
 */
class CycleController extends Controller
{
    public function __construct(private PerformanceScoringService $scoring) {}

    private function gate(string $action): void
    {
        abort_unless(Auth::guard('admin')->user()->can("performance.{$action}"), 403);
    }

    public function index(Request $request)
    {
        $this->gate('view');

        $cycles = PerformanceCycle::withCount(['employeeKras', 'scores'])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->orderByDesc('period_start')
            ->paginate(15)
            ->withQueryString();

        return view('admin.hr.performance.cycles.index', compact('cycles'));
    }

    public function create()
    {
        $this->gate('configure');

        // Suggest the period straight after the most recent cycle, so creating
        // the next quarter is a two-click job.
        $latest = PerformanceCycle::orderByDesc('period_end')->first();
        $suggestion = $latest
            ? $latest->nextPeriod()
            : $this->firstCycleSuggestion();

        return view('admin.hr.performance.cycles.form', [
            'cycle' => new PerformanceCycle(array_merge($suggestion, ['frequency' => $latest?->frequency ?? 'quarterly'])),
        ]);
    }

    public function store(StoreCycleRequest $request)
    {
        $this->gate('configure');

        $cycle = PerformanceCycle::create($request->validated() + [
            'status' => 'draft',
            'created_by' => Auth::guard('admin')->id(),
        ]);

        return redirect()->route('admin.hr.performance.cycles.show', $cycle)
            ->with('success', "Cycle \"{$cycle->name}\" created as a draft. Open it when you are ready for assessments.");
    }

    public function show(PerformanceCycle $cycle)
    {
        $this->gate('view');

        // Stage counts drive the progress strip: how far through the escalation
        // trail this cycle actually is.
        $stages = EmployeeKra::where('performance_cycle_id', $cycle->id)
            ->selectRaw('status, COUNT(DISTINCT employee_id) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $scores = PerformanceScore::with('band')
            ->where('performance_cycle_id', $cycle->id)
            ->orderByDesc('final_score')
            ->get();

        return view('admin.hr.performance.cycles.show', [
            'cycle' => $cycle->load('creator', 'locker'),
            'stages' => $stages,
            'employeeCount' => EmployeeKra::where('performance_cycle_id', $cycle->id)->distinct('employee_id')->count('employee_id'),
            'scores' => $scores,
            'bellCurveEnabled' => $this->scoring->bellCurveEnabled($cycle->business_id),
        ]);
    }

    public function edit(PerformanceCycle $cycle)
    {
        $this->gate('configure');

        return view('admin.hr.performance.cycles.form', compact('cycle'));
    }

    public function update(StoreCycleRequest $request, PerformanceCycle $cycle)
    {
        $this->gate('configure');

        $cycle->update($request->validated());

        return redirect()->route('admin.hr.performance.cycles.show', $cycle)
            ->with('success', 'Cycle updated.');
    }

    /**
     * Move a cycle through draft → open → locked → closed.
     *
     * Locking is the point of the control: once locked, no assessment or score
     * can be changed, which is what makes a finalised appraisal defensible.
     */
    public function transition(Request $request, PerformanceCycle $cycle)
    {
        $this->gate('configure');

        $data = $request->validate([
            'status' => ['required', 'in:draft,open,locked,closed'],
        ]);

        $from = $cycle->status;
        $to = $data['status'];

        if ($from === $to) {
            return back();
        }

        // Re-opening a locked cycle is allowed but deliberately loud: it is the
        // one way a signed-off score can move again.
        $cycle->update([
            'status' => $to,
            'locked_at' => $to === 'locked' ? now() : null,
            'locked_by' => $to === 'locked' ? Auth::guard('admin')->id() : null,
        ]);

        $message = match ($to) {
            'open' => 'Cycle opened — self-assessments and reviews are now accepted.',
            'locked' => 'Cycle locked. Scores are frozen and cannot be edited.',
            'closed' => 'Cycle closed.',
            default => 'Cycle returned to draft.',
        };

        return back()->with('success', $message);
    }

    public function destroy(PerformanceCycle $cycle)
    {
        $this->gate('configure');

        // A cycle with finalised scores is a record, not a draft.
        if ($cycle->scores()->exists()) {
            return back()->with('error', 'This cycle has finalised scores and cannot be deleted. Close it instead.');
        }

        $name = $cycle->name;
        $cycle->delete();

        return redirect()->route('admin.hr.performance.cycles.index')
            ->with('success', "Cycle \"{$name}\" deleted.");
    }

    /**
     * Roll the cycle forward: create the next period with the same frequency.
     */
    public function rollOver(PerformanceCycle $cycle)
    {
        $this->gate('configure');

        $next = $cycle->nextPeriod();

        if (PerformanceCycle::where('period_start', $next['period_start'])->exists()) {
            return back()->with('warning', 'The next cycle for this period already exists.');
        }

        $new = PerformanceCycle::create($next + [
            'frequency' => $cycle->frequency,
            // Due dates keep the same offsets from the period end as this cycle
            // used, so a rolled-over cycle arrives with a sensible calendar.
            'self_review_due' => $this->shiftDue($cycle, $cycle->self_review_due, $next['period_end']),
            'manager_review_due' => $this->shiftDue($cycle, $cycle->manager_review_due, $next['period_end']),
            'hr_review_due' => $this->shiftDue($cycle, $cycle->hr_review_due, $next['period_end']),
            'status' => 'draft',
            'created_by' => Auth::guard('admin')->id(),
        ]);

        return redirect()->route('admin.hr.performance.cycles.show', $new)
            ->with('success', "Next cycle \"{$new->name}\" created. Copy the goals forward from Goal Assignment.");
    }

    /** Keep a due date the same number of days after the period end. */
    private function shiftDue(PerformanceCycle $cycle, ?Carbon $due, string $newEnd): ?string
    {
        if (! $due) {
            return null;
        }

        $offset = $cycle->period_end->diffInDays($due, false);

        return Carbon::parse($newEnd)->addDays((int) $offset)->toDateString();
    }

    /** A sensible first cycle: the quarter we are currently in. */
    private function firstCycleSuggestion(): array
    {
        $start = now()->startOfQuarter();

        return [
            'name' => PerformanceCycle::suggestName('quarterly', $start),
            'period_start' => $start->toDateString(),
            'period_end' => now()->endOfQuarter()->toDateString(),
        ];
    }
}
