<?php

namespace App\Http\Controllers\Admin\Hr\Performance;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeKra;
use App\Models\Kpi;
use App\Models\Kra;
use App\Models\PerformanceCycle;
use App\Models\PerformanceScore;
use App\Services\Performance\PerformanceScoringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * The admin / HR performance dashboard — the nine headline metrics from the
 * proposal, plus the department comparison and the top / low performer lists.
 */
class DashboardController extends Controller
{
    public function __construct(private PerformanceScoringService $scoring) {}

    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('analytics_performance.view'), 403);

        $cycle = $this->resolveCycle($request);
        $cycles = PerformanceCycle::orderByDesc('period_start')->get();

        if (! $cycle) {
            return view('admin.hr.performance.dashboard', [
                'cycle' => null, 'cycles' => $cycles, 'stats' => $this->emptyStats(),
                'byDepartment' => collect(), 'top' => collect(), 'low' => collect(),
                'upcoming' => collect(), 'trend' => collect(), 'bandSpread' => collect(),
            ]);
        }

        $scores = PerformanceScore::with(['employee.department', 'band'])
            ->where('performance_cycle_id', $cycle->id)
            ->whereNotNull('final_score')
            ->get();

        $stats = [
            'employees' => Employee::whereIn('status', ['active', 'probation', 'on_notice'])->count(),
            'active_kras' => Kra::active()->count(),
            'active_kpis' => Kpi::active()->count(),
            'pending_approvals' => EmployeeKra::where('performance_cycle_id', $cycle->id)
                ->whereIn('status', ['assigned', 'self_submitted', 'manager_reviewed'])
                ->distinct('employee_id')->count('employee_id'),
            'completed_reviews' => EmployeeKra::where('performance_cycle_id', $cycle->id)
                ->where('status', 'finalized')
                ->distinct('employee_id')->count('employee_id'),
            'average_score' => $scores->isEmpty() ? null : round((float) $scores->avg('final_score'), 2),
            'in_cycle' => EmployeeKra::where('performance_cycle_id', $cycle->id)
                ->distinct('employee_id')->count('employee_id'),
        ];

        // Department comparison — the chart the proposal asks for.
        $byDepartment = $scores
            ->groupBy(fn (PerformanceScore $s) => $s->employee?->department?->name ?? 'Unassigned')
            ->map(fn ($group) => [
                'employees' => $group->count(),
                'average' => round((float) $group->avg('final_score'), 2),
            ])
            ->sortByDesc('average');

        // Band spread for the donut, ordered best-first.
        $bandSpread = $scores
            ->groupBy(fn (PerformanceScore $s) => $s->band_name ?? 'Unbanded')
            ->map->count();

        // Organisation-wide trend across the last six cycles.
        $trend = PerformanceScore::query()
            ->join('performance_cycles', 'performance_cycles.id', '=', 'performance_scores.performance_cycle_id')
            ->whereNotNull('performance_scores.final_score')
            ->groupBy('performance_cycles.id', 'performance_cycles.name', 'performance_cycles.period_start')
            ->selectRaw('performance_cycles.name as cycle, performance_cycles.period_start,
                         AVG(performance_scores.final_score) as average, COUNT(*) as reviewed')
            ->orderBy('performance_cycles.period_start')
            ->get()
            ->take(-6);

        return view('admin.hr.performance.dashboard', [
            'cycle' => $cycle,
            'cycles' => $cycles,
            'stats' => $stats,
            'byDepartment' => $byDepartment,
            'bandSpread' => $bandSpread,
            'top' => $scores->sortByDesc('final_score')->take(5),
            'low' => $scores->sortBy('final_score')->take(5),
            'trend' => $trend,
            'upcoming' => PerformanceCycle::whereIn('status', ['draft', 'open'])
                ->where(fn ($q) => $q->whereNotNull('self_review_due')
                    ->orWhereNotNull('manager_review_due')
                    ->orWhereNotNull('hr_review_due'))
                ->orderBy('period_end')
                ->limit(5)
                ->get(),
            'bellCurveEnabled' => $this->scoring->bellCurveEnabled($cycle->business_id),
        ]);
    }

    private function emptyStats(): array
    {
        return [
            'employees' => Employee::whereIn('status', ['active', 'probation', 'on_notice'])->count(),
            'active_kras' => Kra::active()->count(),
            'active_kpis' => Kpi::active()->count(),
            'pending_approvals' => 0,
            'completed_reviews' => 0,
            'average_score' => null,
            'in_cycle' => 0,
        ];
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
