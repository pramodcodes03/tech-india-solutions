<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(protected AttendanceService $attendance) {}

    public function index()
    {
        $employee = Auth::guard('employee')->user();
        $employee->load('department', 'designation', 'shift', 'reportingManager', 'leaveBalances.leaveType');

        $month = (int) now()->month;
        $year = (int) now()->year;
        $summary = $this->attendance->monthlySummary($employee->id, $month, $year);

        $todayRecord = Attendance::where('employee_id', $employee->id)->whereDate('date', today())->first();

        $upcomingHolidays = Holiday::whereDate('date', '>=', today())
            ->orderBy('date')->limit(5)->get();

        $pendingLeaves = $employee->leaveRequests()->where('status', 'pending')->count();
        $recentPayslips = $employee->payslips()->latest()->limit(3)->get();
        $openWarnings = $employee->warnings()->where('status', 'active')->count();
        $birthdaysThisMonth = \App\Models\Employee::whereMonth('date_of_birth', now()->month)
            ->where('status', 'active')
            ->orderByRaw('DAY(date_of_birth)')
            ->limit(8)
            ->get();

        // ── Chart 1: Attendance — last 6 months (stacked) ──────────────
        $attendanceTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = Carbon::today()->subMonths($i);
            $rows = Attendance::where('employee_id', $employee->id)
                ->whereYear('date', $m->year)
                ->whereMonth('date', $m->month)
                ->select('status', DB::raw('COUNT(*) as cnt'))
                ->groupBy('status')
                ->pluck('cnt', 'status')->toArray();
            $attendanceTrend[] = [
                'label'    => $m->format('M Y'),
                'present'  => (int) ($rows['present'] ?? 0),
                'late'     => (int) ($rows['late'] ?? 0),
                'half_day' => (int) ($rows['half_day'] ?? 0),
                'absent'   => (int) ($rows['absent'] ?? 0),
                'on_leave' => (int) ($rows['on_leave'] ?? 0),
            ];
        }

        // ── Chart 2: Current month donut ───────────────────────────────
        $currentMonthDonut = [
            'present'  => (int) ($summary['present'] ?? 0),
            'late'     => (int) ($summary['late'] ?? 0),
            'half_day' => (int) ($summary['half_day'] ?? 0),
            'on_leave' => (int) ($summary['on_leave'] ?? 0),
            'absent'   => (int) ($summary['absent'] ?? 0),
        ];

        // ── Chart 3: Leave usage (radialBar) ───────────────────────────
        $leaveUsage = [];
        foreach ($employee->leaveBalances->where('year', $year) as $b) {
            $total = (float) ($b->allocated + $b->carried_forward);
            if ($total <= 0) continue;
            $usedPct = (int) round((((float) $b->used + (float) $b->pending) / $total) * 100);
            $leaveUsage[] = [
                'name'     => $b->leaveType->name,
                'color'    => $b->leaveType->color ?? '#3b82f6',
                'used_pct' => min(100, $usedPct),
                'used'     => (float) $b->used,
                'pending'  => (float) $b->pending,
                'allocated'=> $total,
            ];
        }

        // ── Chart 4: Check-in time trend (last 30 days) ────────────────
        $checkInTrend = [];
        $start = Carbon::today()->subDays(29);
        $records = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$start, Carbon::today()])
            ->whereNotNull('check_in')
            ->orderBy('date')
            ->get(['date', 'check_in']);
        foreach ($records as $r) {
            $time = Carbon::parse($r->check_in);
            $hours = $time->hour + ($time->minute / 60);
            $checkInTrend[] = [
                'label' => Carbon::parse($r->date)->format('d M'),
                'time'  => round($hours, 2),
                'display' => $time->format('g:i A'),
            ];
        }

        return view('employee.dashboard', compact(
            'employee', 'summary', 'todayRecord', 'upcomingHolidays',
            'pendingLeaves', 'recentPayslips', 'openWarnings', 'birthdaysThisMonth',
            'month', 'year',
            'attendanceTrend', 'currentMonthDonut', 'leaveUsage', 'checkInTrend'
        ));
    }
}
