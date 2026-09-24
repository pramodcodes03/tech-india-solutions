<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\BreakSheet;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * An employee's own break sheet.
 *
 * The break register already existed, but only under HR → Trackers, so an
 * employee could never see the breaks recorded against them. This is the
 * read-only employee view of the same table: their own rows, nobody else's.
 *
 * Deliberately read-only — breaks are recorded by the floor supervisor through
 * the tracker, and letting an employee add or edit their own would undermine
 * the point of keeping the register.
 */
class BreakSheetController extends Controller
{
    /** Their breaks for one month, newest first, with a summary strip. */
    public function index(Request $request)
    {
        $employee = Auth::guard('employee')->user();

        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();

        $breaks = BreakSheet::with('breakType')
            ->where('employee_id', $employee->id)
            ->whereBetween('break_date', [$start->toDateString(), $end->toDateString()])
            ->orderByDesc('break_date')
            ->orderByDesc('out_time')
            ->paginate(50)
            ->withQueryString();

        return view('employee.break-sheet.index', [
            'breaks' => $breaks,
            'month' => $month,
            'year' => $year,
            'summary' => self::summaryFor($employee->id, $month, $year),
        ]);
    }

    /**
     * Break totals for one employee in one month.
     *
     * Shared with the dashboard card so the two can never disagree about how
     * long someone has been on break.
     *
     * @return array{count:int, minutes:int, open:int, today_count:int, today_minutes:int}
     */
    public static function summaryFor(int $employeeId, int $month, int $year): array
    {
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();

        $rows = BreakSheet::where('employee_id', $employeeId)
            ->whereBetween('break_date', [$start->toDateString(), $end->toDateString()])
            ->get(['break_date', 'in_time', 'duration_minutes']);

        $today = Carbon::today()->toDateString();
        $todayRows = $rows->filter(fn ($r) => $r->break_date->toDateString() === $today);

        return [
            'count' => $rows->count(),
            'minutes' => (int) $rows->sum('duration_minutes'),
            // A row with no in_time is someone still away from their desk.
            'open' => $rows->whereNull('in_time')->count(),
            'today_count' => $todayRows->count(),
            'today_minutes' => (int) $todayRows->sum('duration_minutes'),
        ];
    }

    /** "1h 24m", or "—" when there is nothing to show. */
    public static function humanMinutes(?int $minutes): string
    {
        $minutes = (int) $minutes;

        if ($minutes <= 0) {
            return '—';
        }

        $hours = intdiv($minutes, 60);
        $rest = $minutes % 60;

        return $hours > 0 ? "{$hours}h {$rest}m" : "{$rest}m";
    }
}
