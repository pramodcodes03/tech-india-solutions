<?php

namespace App\Services\Tracker;

use App\Models\BreakSheet;
use App\Models\DieselBudget;
use App\Models\DieselEntry;
use App\Models\VisitorLog;
use App\Support\SqlDialect;
use App\Support\TrackerFilter;
use Illuminate\Support\Collection;

/**
 * Every number behind the three trackers' analytics screens.
 *
 * Kept out of the controllers because the same aggregates feed both the on-screen
 * charts and the Excel / PDF exports of those screens — one definition of
 * "average break duration" for both.
 */
class TrackerAnalyticsService
{
    // ── Break Sheet ──────────────────────────────────────────────────────

    /**
     * @return array{
     *   totals: array{entries:int, minutes:int, employees:int, avg_minutes:float, avg_per_day:float},
     *   per_employee: Collection, per_employee_day: Collection,
     *   per_department: Collection,
     *   longest: Collection, daily: Collection, by_type: Collection
     * }
     */
    public function breaks(TrackerFilter $filter): array
    {
        $scoped = fn () => $filter->apply(BreakSheet::query(), 'break_date');

        $totals = $scoped()
            ->selectRaw('COUNT(*) as entries, COALESCE(SUM(duration_minutes),0) as minutes, COUNT(DISTINCT employee_id) as employees')
            ->first();

        $entries = (int) ($totals->entries ?? 0);
        $minutes = (int) ($totals->minutes ?? 0);
        $days = max($filter->days(), 1);

        $perEmployee = $scoped()
            ->join('employees', 'employees.id', '=', 'break_sheets.employee_id')
            ->leftJoin('departments', 'departments.id', '=', 'employees.department_id')
            ->groupBy('employees.id', 'employees.employee_code', 'employees.first_name', 'employees.last_name', 'departments.name')
            ->selectRaw('employees.id as employee_id, employees.employee_code,
                         TRIM(CONCAT(employees.first_name, \' \', COALESCE(employees.last_name, \'\'))) as employee_name,
                         departments.name as department,
                         COUNT(*) as entries,
                         COALESCE(SUM(break_sheets.duration_minutes),0) as total_minutes,
                         COALESCE(AVG(break_sheets.duration_minutes),0) as avg_minutes,
                         COALESCE(MAX(break_sheets.duration_minutes),0) as longest_minutes')
            ->orderByDesc('total_minutes')
            ->get();

        $perDepartment = $scoped()
            ->join('employees', 'employees.id', '=', 'break_sheets.employee_id')
            ->leftJoin('departments', 'departments.id', '=', 'employees.department_id')
            ->groupBy('departments.id', 'departments.name')
            ->selectRaw('COALESCE(departments.name, \'Unassigned\') as department,
                         COUNT(*) as entries,
                         COUNT(DISTINCT employees.id) as employees,
                         COALESCE(SUM(break_sheets.duration_minutes),0) as total_minutes,
                         COALESCE(AVG(break_sheets.duration_minutes),0) as avg_minutes')
            ->orderByDesc('total_minutes')
            ->get();

        $longest = $scoped()
            ->with(['employee:id,first_name,last_name,employee_code', 'breakType:id,name'])
            ->whereNotNull('duration_minutes')
            ->orderByDesc('duration_minutes')
            ->limit(10)
            ->get();

        $daily = $scoped()
            ->groupBy('break_date')
            ->selectRaw('break_date, COUNT(*) as entries, COALESCE(SUM(duration_minutes),0) as total_minutes')
            ->orderBy('break_date')
            ->get();

        // One row per employee per day — the view HR reads the paper sheet
        // as: how long was this person off the floor on this date, across all
        // their segments. 'daily' above totals the whole floor per day and
        // 'per_employee' totals a person across the period; neither answers it.
        $perEmployeeDay = $scoped()
            ->join('employees', 'employees.id', '=', 'break_sheets.employee_id')
            ->leftJoin('departments', 'departments.id', '=', 'employees.department_id')
            ->groupBy('break_sheets.break_date', 'employees.id', 'employees.employee_code',
                'employees.first_name', 'employees.last_name', 'departments.name')
            ->selectRaw('break_sheets.break_date,
                         employees.id as employee_id, employees.employee_code,
                         TRIM(CONCAT(employees.first_name, \' \', COALESCE(employees.last_name, \'\'))) as employee_name,
                         departments.name as department,
                         COUNT(*) as entries,
                         COALESCE(SUM(break_sheets.duration_minutes),0) as total_minutes,
                         MIN(break_sheets.out_time) as first_out,
                         MAX(COALESCE(break_sheets.in_time, break_sheets.out_time)) as last_in')
            ->orderByDesc('break_sheets.break_date')
            ->orderByDesc('total_minutes')
            ->get();

        $byType = $scoped()
            ->leftJoin('tracker_options', 'tracker_options.id', '=', 'break_sheets.break_type_id')
            ->groupBy('tracker_options.id', 'tracker_options.name')
            ->selectRaw('COALESCE(tracker_options.name, \'Unspecified\') as break_type,
                         COUNT(*) as entries,
                         COALESCE(SUM(break_sheets.duration_minutes),0) as total_minutes')
            ->orderByDesc('entries')
            ->get();

        return [
            'totals' => [
                'entries' => $entries,
                'minutes' => $minutes,
                'employees' => (int) ($totals->employees ?? 0),
                'avg_minutes' => $entries > 0 ? round($minutes / $entries, 1) : 0.0,
                'avg_per_day' => round($minutes / $days, 1),
            ],
            'per_employee' => $perEmployee,
            'per_employee_day' => $perEmployeeDay,
            'per_department' => $perDepartment,
            'longest' => $longest,
            'daily' => $daily,
            'by_type' => $byType,
        ];
    }

    // ── Diesel ───────────────────────────────────────────────────────────

    /**
     * @return array{
     *   totals: array{entries:int, quantity:float, amount:float, avg_rate:float},
     *   budget: array, trend: Collection, rate_movement: Collection,
     *   month_on_month: Collection, by_vehicle: Collection
     * }
     */
    public function diesel(TrackerFilter $filter): array
    {
        $scoped = fn () => $filter->apply(DieselEntry::query(), 'entry_date');

        $totals = $scoped()
            ->selectRaw('COUNT(*) as entries, COALESCE(SUM(quantity),0) as quantity, COALESCE(SUM(amount),0) as amount')
            ->first();

        $quantity = (float) ($totals->quantity ?? 0);
        $amount = (float) ($totals->amount ?? 0);

        // Daily granularity reads well up to about two months; past that the
        // x-axis turns to mush, so roll up to months instead.
        $groupByDay = $filter->days() > 0 && $filter->days() <= 62;
        $monthKey = SqlDialect::monthKey('entry_date');

        $trend = $groupByDay
            ? $scoped()->groupBy('entry_date')
                ->selectRaw('entry_date as bucket, COALESCE(SUM(quantity),0) as quantity, COALESCE(SUM(amount),0) as amount')
                ->orderBy('bucket')->get()
            : $scoped()->groupByRaw($monthKey)
                ->selectRaw("{$monthKey} as bucket, COALESCE(SUM(quantity),0) as quantity, COALESCE(SUM(amount),0) as amount")
                ->orderBy('bucket')->get();

        $rateMovement = $trend->map(fn ($row) => [
            'bucket' => (string) $row->bucket,
            'rate' => $row->quantity > 0 ? round($row->amount / $row->quantity, 2) : 0,
        ]);

        // Month-on-month always looks back 6 months from the end of the selected
        // period, independent of the filter, so a one-day view still has context.
        $anchor = ($filter->end ?? now())->copy()->endOfMonth();
        $monthOnMonth = DieselEntry::query()
            ->whereDate('entry_date', '>=', $anchor->copy()->subMonths(5)->startOfMonth())
            ->whereDate('entry_date', '<=', $anchor)
            ->groupByRaw($monthKey)
            ->selectRaw("{$monthKey} as bucket, COALESCE(SUM(quantity),0) as quantity, COALESCE(SUM(amount),0) as amount, COUNT(*) as entries")
            ->orderBy('bucket')
            ->get();

        $byVehicle = $scoped()
            ->groupBy('vehicle_no')
            ->selectRaw('COALESCE(NULLIF(vehicle_no, \'\'), \'Unassigned\') as vehicle_no,
                         COUNT(*) as entries, COALESCE(SUM(quantity),0) as quantity, COALESCE(SUM(amount),0) as amount')
            ->orderByDesc('amount')
            ->get();

        return [
            'totals' => [
                'entries' => (int) ($totals->entries ?? 0),
                'quantity' => $quantity,
                'amount' => $amount,
                'avg_rate' => $quantity > 0 ? round($amount / $quantity, 2) : 0.0,
            ],
            'budget' => $this->dieselBudget($filter),
            'trend' => $trend,
            'rate_movement' => $rateMovement,
            'month_on_month' => $monthOnMonth,
            'by_vehicle' => $byVehicle,
        ];
    }

    /**
     * Allocated vs consumed vs remaining for the months the filter touches.
     *
     * @return array{allocated:float, consumed:float, remaining:float, percent:float, months:int, has_budget:bool}
     */
    public function dieselBudget(TrackerFilter $filter): array
    {
        $start = $filter->start?->copy()->startOfMonth();
        $end = $filter->end?->copy()->endOfMonth();

        $budgetQuery = DieselBudget::query();
        if ($start) {
            $budgetQuery->whereDate('period_month', '>=', $start);
        }
        if ($end) {
            $budgetQuery->whereDate('period_month', '<=', $end);
        }

        $allocated = (float) $budgetQuery->sum('amount');
        $months = (clone $budgetQuery)->count();

        $consumed = (float) $filter->apply(DieselEntry::query(), 'entry_date')->sum('amount');

        return [
            'allocated' => $allocated,
            'consumed' => $consumed,
            'remaining' => $allocated - $consumed,
            'percent' => $allocated > 0 ? min(round($consumed / $allocated * 100, 1), 999) : 0.0,
            'months' => $months,
            'has_budget' => $allocated > 0,
        ];
    }

    // ── Visitors ─────────────────────────────────────────────────────────

    /**
     * @return array{
     *   totals: array{visits:int, attended:int, selected:int, joined:int, conversion:float, attendance_rate:float},
     *   by_source: Collection, by_purpose: Collection, by_day: Collection,
     *   by_status: Collection, by_outcome: Collection
     * }
     */
    public function visitors(TrackerFilter $filter): array
    {
        $scoped = fn () => $filter->apply(VisitorLog::query(), 'visit_date');

        $visits = (int) $scoped()->count();
        $attended = (int) $scoped()->where('availability_status', 'available')->count();
        $selected = (int) $scoped()->whereIn('outcome', ['selected', 'joined'])->count();
        $joined = (int) $scoped()->where('outcome', 'joined')->count();

        $bySource = $scoped()
            ->leftJoin('tracker_options', 'tracker_options.id', '=', 'visitor_logs.source_id')
            ->groupBy('tracker_options.id', 'tracker_options.name')
            ->selectRaw('COALESCE(tracker_options.name, \'Unspecified\') as label, COUNT(*) as total,
                         SUM(CASE WHEN visitor_logs.outcome IN (\'selected\',\'joined\') THEN 1 ELSE 0 END) as converted')
            ->orderByDesc('total')
            ->get();

        $byPurpose = $scoped()
            ->leftJoin('tracker_options', 'tracker_options.id', '=', 'visitor_logs.purpose_id')
            ->groupBy('tracker_options.id', 'tracker_options.name')
            ->selectRaw('COALESCE(tracker_options.name, \'Unspecified\') as label, COUNT(*) as total')
            ->orderByDesc('total')
            ->get();

        $byDay = $scoped()
            ->groupBy('visit_date')
            ->selectRaw('visit_date, COUNT(*) as total')
            ->orderBy('visit_date')
            ->get();

        $byStatus = $scoped()
            ->groupBy('availability_status')
            ->selectRaw('availability_status as label, COUNT(*) as total')
            ->orderByDesc('total')
            ->get();

        $byOutcome = $scoped()
            ->groupBy('outcome')
            ->selectRaw('outcome as label, COUNT(*) as total')
            ->orderByDesc('total')
            ->get();

        return [
            'totals' => [
                'visits' => $visits,
                'attended' => $attended,
                'selected' => $selected,
                'joined' => $joined,
                // Conversion is measured against people who actually turned up —
                // counting no-shows in the denominator would punish the source
                // for something the interview never got to test.
                'conversion' => $attended > 0 ? round($selected / $attended * 100, 1) : 0.0,
                'attendance_rate' => $visits > 0 ? round($attended / $visits * 100, 1) : 0.0,
            ],
            'by_source' => $bySource,
            'by_purpose' => $byPurpose,
            'by_day' => $byDay,
            'by_status' => $byStatus,
            'by_outcome' => $byOutcome,
        ];
    }
}
