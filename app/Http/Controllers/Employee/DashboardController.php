<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Holiday;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

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

        return view('employee.dashboard', compact(
            'employee', 'summary', 'todayRecord', 'upcomingHolidays',
            'pendingLeaves', 'recentPayslips', 'openWarnings', 'birthdaysThisMonth',
            'month', 'year'
        ));
    }
}
