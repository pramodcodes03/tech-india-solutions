<?php

namespace App\Http\Controllers\Admin\Hr\Trackers;

use App\Http\Controllers\Concerns\BulkDeletesRows;
use App\Http\Controllers\Concerns\ExportsTrackerRegisters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Tracker\StoreBreakSheetRequest;
use App\Models\BreakSheet;
use App\Models\Department;
use App\Models\Employee;
use App\Models\TrackerOption;
use App\Services\Tracker\TrackerAnalyticsService;
use App\Support\TrackerFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Break Sheet Tracker — the register of employee breaks.
 *
 * Total timing is never typed by a user: it is derived from out/in time on
 * every save so the register and its analytics can never disagree.
 */
class BreakSheetController extends Controller
{
    use BulkDeletesRows, ExportsTrackerRegisters;

    /** Request sort key => orderable column. */
    private const SORTABLE = [
        'date' => 'break_sheets.break_date',
        'employee' => 'employees.first_name',
        'out' => 'break_sheets.out_time',
        'in' => 'break_sheets.in_time',
        'duration' => 'break_sheets.duration_minutes',
        'created' => 'break_sheets.created_at',
    ];

    /**
     * Applied under whichever column the user sorted on, so one employee's
     * breaks for a date stay adjacent and in the order they were taken. The
     * combined "Total Break Timing" is printed once per run, so a grouping
     * that scattered would print it repeatedly instead of reading as one block.
     */
    private const GROUPING = ['employees.first_name', 'break_sheets.employee_id', 'break_sheets.out_time'];

    public function __construct(private TrackerAnalyticsService $analytics) {}

    private function gate(string $action): void
    {
        abort_unless(Auth::guard('admin')->user()->can("break_tracker.{$action}"), 403);
    }

    public function index(Request $request)
    {
        $this->gate('view');

        $filter = TrackerFilter::fromRequest($request);
        $query = $this->baseQuery($request, $filter);

        $sorting = $this->applySort($query, $request, self::SORTABLE, 'date', self::GROUPING);

        $entries = $query
            ->with(['employee.department', 'breakType'])
            ->select('break_sheets.*')
            ->paginate(25)
            ->withQueryString();

        // Summary strip is computed on the whole filtered set, not the page.
        $summaryQuery = $this->baseQuery($request, $filter);
        $summary = $summaryQuery
            ->selectRaw('COUNT(*) as entries, COALESCE(SUM(break_sheets.duration_minutes),0) as minutes, COUNT(DISTINCT break_sheets.employee_id) as employees')
            ->first();

        // Combined break time per employee per day. Computed over the whole
        // filtered set, not the page, so a person's segments still add up when
        // they straddle a page boundary.
        $dailyTotals = BreakSheet::dailyTotals($this->baseQuery($request, $filter));

        return view('admin.hr.trackers.break.index', [
            'entries' => $entries,
            'dailyTotals' => $dailyTotals,
            'filter' => $filter,
            'sorting' => $sorting,
            'summary' => $summary,
            'departments' => Department::orderBy('name')->get(),
            'breakTypes' => TrackerOption::listFor(TrackerOption::TYPE_BREAK),
        ]);
    }

    public function analytics(Request $request)
    {
        $this->gate('view');

        $filter = TrackerFilter::fromRequest($request);

        return view('admin.hr.trackers.break.analytics', [
            'filter' => $filter,
            'data' => $this->analytics->breaks($filter),
        ]);
    }

    public function create()
    {
        $this->gate('create');

        return view('admin.hr.trackers.break.form', [
            'entry' => new BreakSheet(['break_date' => now()->toDateString()]),
            'employees' => $this->employees(),
            'breakTypes' => TrackerOption::listFor(TrackerOption::TYPE_BREAK),
        ]);
    }

    public function store(StoreBreakSheetRequest $request)
    {
        $this->gate('create');

        BreakSheet::create($this->payload($request));

        return redirect()->route('admin.hr.trackers.break.index')
            ->with('success', 'Break entry recorded.');
    }

    public function edit(BreakSheet $break)
    {
        $this->gate('edit');

        return view('admin.hr.trackers.break.form', [
            'entry' => $break,
            'employees' => $this->employees(),
            'breakTypes' => TrackerOption::listFor(TrackerOption::TYPE_BREAK),
        ]);
    }

    public function update(StoreBreakSheetRequest $request, BreakSheet $break)
    {
        $this->gate('edit');

        $break->update($this->payload($request, $break));

        return redirect()->route('admin.hr.trackers.break.index')
            ->with('success', 'Break entry updated.');
    }

    /**
     * Delete the ticked rows, or the single row whose Delete button was used.
     */
    public function bulkDestroy(Request $request)
    {
        $this->gate('delete');

        return $this->bulkDeleteRows(
            $request,
            BreakSheet::class,
            'break entry',
            'break entries',
        );
    }

    public function destroy(BreakSheet $break)
    {
        $this->gate('delete');

        $break->delete();

        return back()->with('success', 'Break entry deleted.');
    }

    public function export(Request $request)
    {
        $this->gate('export');

        $filter = TrackerFilter::fromRequest($request);
        $query = $this->baseQuery($request, $filter);
        $this->applySort($query, $request, self::SORTABLE, 'date', self::GROUPING);

        $entries = $query->with(['employee.department', 'breakType'])->select('break_sheets.*')->get();

        $dailyTotals = BreakSheet::dailyTotals($this->baseQuery($request, $filter));

        // The sheet HR already works from carries both figures side by side:
        // this segment's length, and the day's combined total for the person.
        // Printing the total on every row of the group (rather than only the
        // first) keeps it correct whatever column the register is sorted on.
        $rows = $entries->map(fn (BreakSheet $b) => [
            $b->break_date->format('d-m-Y'),
            $b->employee?->employee_code,
            $b->employee?->full_name,
            $b->employee?->department?->name,
            substr((string) $b->out_time, 0, 5),
            $b->in_time ? substr((string) $b->in_time, 0, 5) : '',
            $b->duration_label,
            BreakSheet::formatMinutes($dailyTotals[$b->group_key]['minutes'] ?? null),
            $b->breakType?->name,
            $b->remarks,
        ])->all();

        $totalMinutes = (int) $entries->sum('duration_minutes');

        return $this->streamExport(
            $request,
            $filter,
            'Break Sheet Register',
            ['Date', 'Employee ID', 'Employee', 'Department', 'Out Time', 'In Time', 'Total Timing', 'Total Break Timing', 'Break Type', 'Remarks'],
            $rows,
            [
                'Entries' => (string) $entries->count(),
                'Employees' => (string) $entries->pluck('employee_id')->unique()->count(),
                'Total break time' => BreakSheet::formatMinutes($totalMinutes),
                'Average break' => BreakSheet::formatMinutes($entries->count() ? $totalMinutes / $entries->count() : null),
            ],
        );
    }

    /**
     * The register query every action shares: period filter, free-text search,
     * department and break-type narrowing.
     */
    private function baseQuery(Request $request, TrackerFilter $filter)
    {
        $query = BreakSheet::query()
            ->join('employees', 'employees.id', '=', 'break_sheets.employee_id');

        $filter->apply($query, 'break_sheets.break_date');

        return $query
            ->when($request->input('search'), fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('employees.first_name', 'like', "%{$s}%")
                    ->orWhere('employees.last_name', 'like', "%{$s}%")
                    ->orWhere('employees.employee_code', 'like', "%{$s}%")
                    ->orWhere('break_sheets.remarks', 'like', "%{$s}%");
            }))
            ->when($request->input('department_id'), fn ($q, $v) => $q->where('employees.department_id', $v))
            ->when($request->input('break_type_id'), fn ($q, $v) => $q->where('break_sheets.break_type_id', $v));
    }

    /** Employees offered in the dropdown — the same set attendance uses. */
    private function employees()
    {
        return Employee::whereIn('status', ['active', 'probation', 'on_notice'])
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'employee_code', 'department_id']);
    }

    /** Validated input plus the derived duration and the recording admin. */
    private function payload(StoreBreakSheetRequest $request, ?BreakSheet $existing = null): array
    {
        $data = $request->validated();
        $data['duration_minutes'] = BreakSheet::minutesBetween($data['out_time'], $data['in_time'] ?? null);
        $data['recorded_by'] = $existing?->recorded_by ?? Auth::guard('admin')->id();

        return $data;
    }
}
