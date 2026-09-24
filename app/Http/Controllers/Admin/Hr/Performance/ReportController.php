<?php

namespace App\Http\Controllers\Admin\Hr\Performance;

use App\Exports\GenericArrayExport;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\EmployeeKpi;
use App\Models\EmployeeKra;
use App\Models\PerformanceCycle;
use App\Models\PerformanceManagerReview;
use App\Models\PerformanceScore;
use App\Support\Tenancy\CurrentBusiness;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

/**
 * The thirteen performance reports, each filterable and exportable to Excel
 * and PDF.
 *
 * One builder per report returns [title, headings, rows, summary]; the shared
 * render/export path takes it from there, so adding a fourteenth report is a
 * method rather than a screen.
 */
class ReportController extends Controller
{
    /** key => human label, in the order the proposal lists them. */
    public const REPORTS = [
        'employee' => 'Employee Performance',
        'department' => 'Department Report',
        'manager' => 'Manager Report',
        'kra_completion' => 'KRA Completion',
        'kpi_achievement' => 'KPI Achievement',
        'monthly' => 'Monthly Performance',
        'quarterly' => 'Quarterly Performance',
        'annual' => 'Annual Performance',
        'top_performers' => 'Top Performers',
        'bottom_performers' => 'Bottom Performers',
        'appraisal' => 'Appraisal Report',
        'increment' => 'Increment Recommendation',
        'promotion' => 'Promotion Eligibility',
    ];

    private function gate(string $action = 'view'): void
    {
        abort_unless(Auth::guard('admin')->user()->can("performance_reports.{$action}"), 403);
    }

    /** The hub: one card per report. */
    public function index(Request $request)
    {
        $this->gate();

        return view('admin.hr.performance.reports.index', [
            'reports' => self::REPORTS,
            'cycles' => PerformanceCycle::orderByDesc('period_start')->get(),
            'cycle' => $this->resolveCycle($request),
        ]);
    }

    /** Run one report — on screen, or exported when ?format= is present. */
    public function show(Request $request, string $report)
    {
        $this->gate();

        abort_unless(array_key_exists($report, self::REPORTS), 404);

        $cycle = $this->resolveCycle($request);
        $departmentId = $request->input('department_id');

        $built = $this->build($report, $cycle, $departmentId);
        $built['title'] = self::REPORTS[$report];

        if ($format = $request->input('format')) {
            $this->gate('export');

            return $this->export($format, $built, $cycle);
        }

        return view('admin.hr.performance.reports.show', [
            'report' => $report,
            'title' => self::REPORTS[$report],
            'built' => $built,
            'cycle' => $cycle,
            'cycles' => PerformanceCycle::orderByDesc('period_start')->get(),
            'departments' => Department::orderBy('name')->get(),
            'reports' => self::REPORTS,
        ]);
    }

    // ── Builders ─────────────────────────────────────────────────────────

    /**
     * @return array{headings: array<int,string>, rows: array<int,array>, summary: array<string,string>}
     */
    private function build(string $report, ?PerformanceCycle $cycle, mixed $departmentId): array
    {
        if (! $cycle && ! in_array($report, ['monthly', 'quarterly', 'annual'], true)) {
            return ['headings' => [], 'rows' => [], 'summary' => []];
        }

        return match ($report) {
            'department' => $this->departmentReport($cycle),
            'manager' => $this->managerReport($cycle),
            'kra_completion' => $this->kraCompletionReport($cycle, $departmentId),
            'kpi_achievement' => $this->kpiAchievementReport($cycle, $departmentId),
            'monthly' => $this->periodReport('monthly'),
            'quarterly' => $this->periodReport('quarterly'),
            'annual' => $this->periodReport('yearly'),
            'top_performers' => $this->performerReport($cycle, $departmentId, true),
            'bottom_performers' => $this->performerReport($cycle, $departmentId, false),
            'appraisal' => $this->appraisalReport($cycle, $departmentId),
            'increment' => $this->rewardReport($cycle, $departmentId, ['increment', 'bonus']),
            'promotion' => $this->rewardReport($cycle, $departmentId, ['promotion']),
            default => $this->employeeReport($cycle, $departmentId),
        };
    }

    private function employeeReport(PerformanceCycle $cycle, mixed $departmentId): array
    {
        $scores = $this->scoreQuery($cycle, $departmentId)->get();

        $rows = $scores->map(fn (PerformanceScore $s) => [
            $s->employee?->employee_code,
            $s->employee?->full_name,
            $s->employee?->department?->name ?? '—',
            $s->self_score !== null ? number_format((float) $s->self_score, 2) : '—',
            $s->manager_score !== null ? number_format((float) $s->manager_score, 2) : '—',
            number_format((float) $s->final_score, 2),
            $s->band_name ?? '—',
            $s->bell_curve_band ?? '—',
            $s->recommendation_label,
        ])->all();

        return [
            'headings' => ['Employee ID', 'Employee', 'Department', 'Self', 'Manager', 'Final Score', 'Band', 'Bell Curve', 'Recommendation'],
            'rows' => $rows,
            'summary' => $this->scoreSummary($scores),
        ];
    }

    private function departmentReport(PerformanceCycle $cycle): array
    {
        $scores = $this->scoreQuery($cycle, null)->get();

        $rows = $scores
            ->groupBy(fn (PerformanceScore $s) => $s->employee?->department?->name ?? 'Unassigned')
            ->map(fn (Collection $group, string $name) => [
                $name,
                $group->count(),
                number_format((float) $group->avg('final_score'), 2),
                number_format((float) $group->max('final_score'), 2),
                number_format((float) $group->min('final_score'), 2),
                $group->filter(fn ($s) => (float) $s->final_score >= 85)->count(),
                $group->filter(fn ($s) => (float) $s->final_score < 60)->count(),
            ])
            ->sortByDesc(fn ($row) => (float) str_replace(',', '', $row[2]))
            ->values()
            ->all();

        return [
            'headings' => ['Department', 'Reviewed', 'Average', 'Highest', 'Lowest', 'Scoring 85+', 'Below 60'],
            'rows' => $rows,
            'summary' => $this->scoreSummary($scores),
        ];
    }

    private function managerReport(PerformanceCycle $cycle): array
    {
        $goals = EmployeeKra::with(['manager', 'employee'])
            ->where('performance_cycle_id', $cycle->id)
            ->get();

        $rows = $goals
            ->groupBy(fn (EmployeeKra $g) => $g->manager?->full_name ?? 'Unassigned')
            ->map(function (Collection $group, string $manager) {
                $employees = $group->pluck('employee_id')->unique();
                $reviewed = $group->whereIn('status', ['manager_reviewed', 'hr_reviewed', 'finalized'])
                    ->pluck('employee_id')->unique();

                return [
                    $manager,
                    $employees->count(),
                    $group->count(),
                    $reviewed->count(),
                    $employees->count() - $reviewed->count(),
                    $employees->count() > 0 ? round($reviewed->count() / $employees->count() * 100).'%' : '—',
                ];
            })
            ->sortByDesc(fn ($row) => $row[1])
            ->values()
            ->all();

        return [
            'headings' => ['Manager', 'Team Size', 'Goals', 'Reviewed', 'Pending', 'Completion'],
            'rows' => $rows,
            'summary' => ['Managers' => (string) count($rows), 'Goals in cycle' => (string) $goals->count()],
        ];
    }

    private function kraCompletionReport(PerformanceCycle $cycle, mixed $departmentId): array
    {
        $goals = EmployeeKra::with(['kra', 'employee.department', 'kpis'])
            ->where('performance_cycle_id', $cycle->id)
            ->when($departmentId, fn ($q, $id) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $id)))
            ->get();

        $rows = $goals
            ->groupBy(fn (EmployeeKra $g) => $g->kra?->name ?? '—')
            ->map(function (Collection $group, string $name) {
                $finalized = $group->where('status', 'finalized')->count();

                return [
                    $group->first()->kra?->code ?? '—',
                    $name,
                    $group->count(),
                    $finalized,
                    $group->count() > 0 ? round($finalized / $group->count() * 100).'%' : '—',
                    number_format((float) $group->whereNotNull('final_score')->avg('final_score'), 2),
                ];
            })
            ->sortByDesc(fn ($row) => $row[2])
            ->values()
            ->all();

        return [
            'headings' => ['KRA Code', 'KRA', 'Assigned', 'Finalised', 'Completion', 'Average Score'],
            'rows' => $rows,
            'summary' => ['KRAs in play' => (string) count($rows), 'Assignments' => (string) $goals->count()],
        ];
    }

    private function kpiAchievementReport(PerformanceCycle $cycle, mixed $departmentId): array
    {
        $kpis = EmployeeKpi::with(['kpi', 'employeeKra.employee.department'])
            ->whereHas('employeeKra', fn ($q) => $q
                ->where('performance_cycle_id', $cycle->id)
                ->when($departmentId, fn ($q, $id) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $id))))
            ->get();

        $rows = $kpis->map(fn (EmployeeKpi $k) => [
            $k->employeeKra?->employee?->employee_code,
            $k->employeeKra?->employee?->full_name,
            $k->kpi?->name ?? '—',
            $k->kpi?->unit_label ?? '—',
            $k->kpi?->formatValue($k->target_value === null ? null : (float) $k->target_value) ?? '—',
            $k->kpi?->formatValue($k->achieved_value === null ? null : (float) $k->achieved_value) ?? '—',
            $k->achievement_percent !== null ? $k->achievement_percent.'%' : '—',
            $k->score !== null ? number_format((float) $k->score, 2) : '—',
        ])->all();

        $scored = $kpis->whereNotNull('score');

        return [
            'headings' => ['Employee ID', 'Employee', 'KPI', 'Unit', 'Target', 'Achieved', 'Achievement', 'Score'],
            'rows' => $rows,
            'summary' => [
                'KPIs' => (string) $kpis->count(),
                'Measured' => (string) $scored->count(),
                'Average score' => $scored->isEmpty() ? '—' : number_format((float) $scored->avg('score'), 2),
            ],
        ];
    }

    /** Monthly / quarterly / annual: every cycle of that frequency, compared. */
    private function periodReport(string $frequency): array
    {
        $cycles = PerformanceCycle::where('frequency', $frequency)
            ->withCount('scores')
            ->orderBy('period_start')
            ->get();

        $rows = $cycles->map(function (PerformanceCycle $c) {
            $scores = PerformanceScore::where('performance_cycle_id', $c->id)->whereNotNull('final_score')->get();

            return [
                $c->name,
                $c->period_label,
                $c->status_label,
                $scores->count(),
                $scores->isEmpty() ? '—' : number_format((float) $scores->avg('final_score'), 2),
                $scores->isEmpty() ? '—' : number_format((float) $scores->max('final_score'), 2),
                $scores->filter(fn ($s) => (float) $s->final_score >= 85)->count(),
            ];
        })->all();

        return [
            'headings' => ['Cycle', 'Period', 'Status', 'Reviewed', 'Average', 'Highest', 'Scoring 85+'],
            'rows' => $rows,
            'summary' => ['Cycles' => (string) $cycles->count()],
        ];
    }

    private function performerReport(PerformanceCycle $cycle, mixed $departmentId, bool $top): array
    {
        $scores = $this->scoreQuery($cycle, $departmentId)
            ->reorder()
            ->orderBy('final_score', $top ? 'desc' : 'asc')
            ->limit(20)
            ->get();

        $rows = $scores->values()->map(fn (PerformanceScore $s, int $i) => [
            $i + 1,
            $s->employee?->employee_code,
            $s->employee?->full_name,
            $s->employee?->department?->name ?? '—',
            number_format((float) $s->final_score, 2),
            $s->band_name ?? '—',
            $s->recommendation_label,
        ])->all();

        return [
            'headings' => ['#', 'Employee ID', 'Employee', 'Department', 'Final Score', 'Band', 'Recommendation'],
            'rows' => $rows,
            'summary' => $this->scoreSummary($scores),
        ];
    }

    private function appraisalReport(PerformanceCycle $cycle, mixed $departmentId): array
    {
        $scores = $this->scoreQuery($cycle, $departmentId)->with('appraisal')->get();

        $rows = $scores->map(fn (PerformanceScore $s) => [
            $s->employee?->employee_code,
            $s->employee?->full_name,
            $s->employee?->department?->name ?? '—',
            number_format((float) $s->final_score, 2),
            $s->band_name ?? '—',
            $s->recommendation_label,
            PerformanceScore::RECOMMENDATION_STATUSES[$s->recommendation_status] ?? '—',
            $s->appraisal?->appraisal_code ?? 'Not generated',
            $s->appraisal?->recommended_hike_percent !== null
                ? number_format((float) $s->appraisal->recommended_hike_percent, 2).'%'
                : '—',
        ])->all();

        return [
            'headings' => ['Employee ID', 'Employee', 'Department', 'Score', 'Band', 'Recommendation', 'Decision', 'Appraisal', 'Hike'],
            'rows' => $rows,
            'summary' => [
                'Reviewed' => (string) $scores->count(),
                'Appraisals written' => (string) $scores->whereNotNull('appraisal_id')->count(),
            ],
        ];
    }

    /**
     * Increment and promotion reports share a shape — they differ only in which
     * recommendations they list.
     *
     * @param  array<int,string>  $recommendations
     */
    private function rewardReport(PerformanceCycle $cycle, mixed $departmentId, array $recommendations): array
    {
        $scores = $this->scoreQuery($cycle, $departmentId)->get()
            ->filter(fn (PerformanceScore $s) => in_array($s->effective_recommendation, $recommendations, true));

        $managerReviews = PerformanceManagerReview::where('performance_cycle_id', $cycle->id)
            ->get()->keyBy('employee_id');

        $rows = $scores->map(function (PerformanceScore $s) use ($managerReviews) {
            $review = $managerReviews->get($s->employee_id);

            return [
                $s->employee?->employee_code,
                $s->employee?->full_name,
                $s->employee?->department?->name ?? '—',
                $s->employee?->designation?->name ?? '—',
                number_format((float) $s->final_score, 2),
                $s->band_name ?? '—',
                $s->recommendation_label,
                $review?->recommend_promotion ? 'Yes' : 'No',
                PerformanceScore::RECOMMENDATION_STATUSES[$s->recommendation_status] ?? '—',
            ];
        })->values()->all();

        return [
            'headings' => ['Employee ID', 'Employee', 'Department', 'Designation', 'Score', 'Band', 'Recommendation', 'Manager Recommends', 'Decision'],
            'rows' => $rows,
            'summary' => ['Eligible' => (string) count($rows)],
        ];
    }

    // ── Shared ───────────────────────────────────────────────────────────

    private function scoreQuery(PerformanceCycle $cycle, mixed $departmentId)
    {
        return PerformanceScore::with(['employee.department', 'employee.designation', 'band'])
            ->where('performance_cycle_id', $cycle->id)
            ->whereNotNull('final_score')
            ->when($departmentId, fn ($q, $id) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $id)))
            ->orderByDesc('final_score');
    }

    /** @return array<string,string> */
    private function scoreSummary(Collection $scores): array
    {
        if ($scores->isEmpty()) {
            return ['Reviewed' => '0'];
        }

        return [
            'Reviewed' => (string) $scores->count(),
            'Average' => number_format((float) $scores->avg('final_score'), 2),
            'Highest' => number_format((float) $scores->max('final_score'), 2),
            'Lowest' => number_format((float) $scores->min('final_score'), 2),
        ];
    }

    private function export(string $format, array $built, ?PerformanceCycle $cycle)
    {
        $title = $built['title'] ?? 'Performance Report';
        $slug = str($title)->slug().'-'.str($cycle?->name ?? 'all')->slug();

        if ($format === 'pdf') {
            return Pdf::loadView('admin.hr.performance.reports.pdf', [
                'title' => $title,
                'period' => $cycle?->name ?? 'All cycles',
                'headings' => $built['headings'],
                'rows' => $built['rows'],
                'summary' => $built['summary'],
                'business' => app(CurrentBusiness::class)->get(),
            ])->setPaper('a4', count($built['headings']) > 7 ? 'landscape' : 'portrait')
                ->download("{$slug}.pdf");
        }

        return Excel::download(new GenericArrayExport($built['headings'], $built['rows']), "{$slug}.xlsx");
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
