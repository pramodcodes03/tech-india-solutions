<?php

namespace App\Support;

use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\Payslip;
use Illuminate\Support\Facades\DB;

/**
 * Year dropdowns for HR screens.
 *
 * Returns distinct years that actually have data for the given context, unioned
 * with [current − 1, current, current + 1] so the list is never empty and always
 * includes next year for planning. Sorted newest first.
 */
class HrYears
{
    /**
     * Leave balances — distinct years from leave_balances.
     *
     * @return array<int,int>
     */
    public static function forLeaveBalances(): array
    {
        return self::merge(LeaveBalance::query()->distinct()->pluck('year'));
    }

    /**
     * Leave requests — derived from from_date.
     *
     * @return array<int,int>
     */
    public static function forLeaveRequests(): array
    {
        $years = LeaveRequest::query()
            ->selectRaw('DISTINCT '.self::yearExpr('from_date').' as y')
            ->pluck('y');

        return self::merge($years);
    }

    /**
     * Attendance — distinct years from date.
     *
     * @return array<int,int>
     */
    public static function forAttendance(): array
    {
        $years = Attendance::query()
            ->selectRaw('DISTINCT '.self::yearExpr('date').' as y')
            ->pluck('y');

        return self::merge($years);
    }

    /**
     * Payslips — from the explicit year column.
     *
     * @return array<int,int>
     */
    public static function forPayslips(): array
    {
        return self::merge(Payslip::query()->distinct()->pluck('year'));
    }

    /**
     * Holidays — distinct years from date.
     *
     * @return array<int,int>
     */
    public static function forHolidays(): array
    {
        $years = Holiday::query()
            ->selectRaw('DISTINCT '.self::yearExpr('date').' as y')
            ->pluck('y');

        return self::merge($years);
    }

    /**
     * Merge a source list of years with a baseline window so the list always
     * includes at least the current year and next year.
     *
     * @param  iterable<int|string>  $years
     * @return array<int,int>
     */
    private static function merge(iterable $years): array
    {
        $current = (int) date('Y');
        $merged = collect([$current - 1, $current, $current + 1])
            ->merge(collect($years)->map(fn ($y) => (int) $y))
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        return $merged;
    }

    /**
     * Database-agnostic YEAR(col) expression.
     */
    private static function yearExpr(string $column): string
    {
        $driver = DB::connection()->getDriverName();

        return match ($driver) {
            'sqlite' => "CAST(strftime('%Y', {$column}) AS INTEGER)",
            'pgsql' => "EXTRACT(YEAR FROM {$column})",
            default => "YEAR({$column})",
        };
    }
}
