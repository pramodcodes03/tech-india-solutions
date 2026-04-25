<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\Appraisal;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Payslip;
use App\Models\Penalty;
use App\Models\Warning;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        abort_unless(Auth::guard('admin')->user()->can('employees.view'), 403);

        $today = Carbon::today();
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();

        // ── Headline counters ─────────────────────────────────────────────
        $totalEmployees = Employee::count();
        $activeEmployees = Employee::where('status', 'active')->count();
        $onProbation = Employee::where('status', 'probation')->count();
        $onNotice = Employee::where('status', 'on_notice')->count();
        $newThisMonth = Employee::whereBetween('joining_date', [$monthStart, $monthEnd])->count();
        $exitsThisMonth = Employee::whereBetween('last_working_date', [$monthStart, $monthEnd])->count();

        // Today's attendance snapshot
        $presentToday = Attendance::whereDate('date', $today)->where('status', 'present')->count();
        $absentToday = Attendance::whereDate('date', $today)->where('status', 'absent')->count();
        $lateToday = Attendance::whereDate('date', $today)->where('status', 'late')->count();
        $halfDayToday = Attendance::whereDate('date', $today)->where('status', 'half_day')->count();
        $onLeaveToday = LeaveRequest::where('status', 'approved')
            ->whereDate('from_date', '<=', $today)
            ->whereDate('to_date', '>=', $today)
            ->count();

        $attendanceRate = $totalEmployees > 0
            ? round(($presentToday / $totalEmployees) * 100, 1)
            : 0;

        $pendingLeaves = LeaveRequest::where('status', 'pending')->count();
        $activeWarnings = Warning::where('status', 'active')->count();
        $pendingPenalties = Penalty::where('status', 'pending')->sum('amount');

        // Payroll this month
        $payrollThisMonth = Payslip::where('month', $today->month)
            ->where('year', $today->year)
            ->sum('net_pay');
        $payslipsPaid = Payslip::where('month', $today->month)
            ->where('year', $today->year)
            ->where('status', 'paid')
            ->count();
        $payslipsTotal = Payslip::where('month', $today->month)
            ->where('year', $today->year)
            ->count();

        // ── Chart 1: Headcount trend (last 12 months, line) ──────────────
        $headcountTrend = [];
        for ($i = 11; $i >= 0; $i--) {
            $m = Carbon::today()->subMonths($i);
            $count = Employee::where('joining_date', '<=', $m->copy()->endOfMonth())
                ->where(function ($q) use ($m) {
                    $q->whereNull('last_working_date')
                        ->orWhere('last_working_date', '>', $m->copy()->endOfMonth());
                })
                ->count();
            $headcountTrend[] = [
                'label' => $m->format('M Y'),
                'value' => $count,
            ];
        }

        // ── Chart 2: Department headcount (bar) ──────────────────────────
        $deptHeadcount = Department::leftJoin('employees', 'employees.department_id', '=', 'departments.id')
            ->select('departments.name', DB::raw('COUNT(employees.id) as total'))
            ->where('departments.status', 'active')
            ->groupBy('departments.id', 'departments.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // ── Chart 3: Gender split (donut) ────────────────────────────────
        $genderSplit = Employee::select('gender', DB::raw('COUNT(*) as total'))
            ->whereNotNull('gender')
            ->groupBy('gender')
            ->pluck('total', 'gender')
            ->toArray();

        // ── Chart 4: Employment type (polarArea) ─────────────────────────
        $employmentType = Employee::select('employment_type', DB::raw('COUNT(*) as total'))
            ->whereNotNull('employment_type')
            ->groupBy('employment_type')
            ->pluck('total', 'employment_type')
            ->toArray();

        // ── Chart 5: Attendance last 30 days (stacked area) ──────────────
        $attendance30 = [];
        $start = Carbon::today()->subDays(29);
        $rows = Attendance::whereBetween('date', [$start, $today])
            ->select('date', 'status', DB::raw('COUNT(*) as total'))
            ->groupBy('date', 'status')
            ->get()
            ->groupBy(fn ($r) => $r->date->format('Y-m-d'));

        for ($d = 0; $d < 30; $d++) {
            $day = $start->copy()->addDays($d);
            $key = $day->format('Y-m-d');
            $byStatus = ($rows[$key] ?? collect())->pluck('total', 'status');
            $attendance30[] = [
                'date' => $day->format('d M'),
                'present' => (int) ($byStatus['present'] ?? 0),
                'absent' => (int) ($byStatus['absent'] ?? 0),
                'late' => (int) ($byStatus['late'] ?? 0),
                'half_day' => (int) ($byStatus['half_day'] ?? 0),
            ];
        }

        // ── Chart 6: Leaves by type (horizontal bar) this year ──────────
        $leavesByType = LeaveRequest::join('leave_types', 'leave_types.id', '=', 'leave_requests.leave_type_id')
            ->where('leave_requests.status', 'approved')
            ->whereYear('from_date', $today->year)
            ->select('leave_types.name', DB::raw('SUM(leave_requests.days) as total'))
            ->groupBy('leave_types.id', 'leave_types.name')
            ->orderByDesc('total')
            ->get();

        // ── Chart 7: Payroll trend last 6 months (column) ───────────────
        $payrollTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = Carbon::today()->subMonths($i);
            $total = Payslip::where('month', $m->month)->where('year', $m->year)->sum('net_pay');
            $payrollTrend[] = [
                'label' => $m->format('M Y'),
                'value' => (float) $total,
            ];
        }

        // ── Chart 8: Age distribution (histogram) ───────────────────────
        $ageBuckets = ['<25' => 0, '25-30' => 0, '31-35' => 0, '36-40' => 0, '41-50' => 0, '50+' => 0];
        Employee::whereNotNull('date_of_birth')->select('date_of_birth')->chunk(500, function ($chunk) use (&$ageBuckets) {
            foreach ($chunk as $e) {
                $age = Carbon::parse($e->date_of_birth)->age;
                if ($age < 25) $ageBuckets['<25']++;
                elseif ($age <= 30) $ageBuckets['25-30']++;
                elseif ($age <= 35) $ageBuckets['31-35']++;
                elseif ($age <= 40) $ageBuckets['36-40']++;
                elseif ($age <= 50) $ageBuckets['41-50']++;
                else $ageBuckets['50+']++;
            }
        });

        // ── Chart 9: Tenure distribution ────────────────────────────────
        $tenureBuckets = ['< 1 yr' => 0, '1-2 yr' => 0, '2-5 yr' => 0, '5-10 yr' => 0, '10+ yr' => 0];
        Employee::whereNotNull('joining_date')->select('joining_date')->chunk(500, function ($chunk) use (&$tenureBuckets) {
            foreach ($chunk as $e) {
                $years = Carbon::parse($e->joining_date)->diffInYears(now());
                if ($years < 1) $tenureBuckets['< 1 yr']++;
                elseif ($years < 2) $tenureBuckets['1-2 yr']++;
                elseif ($years < 5) $tenureBuckets['2-5 yr']++;
                elseif ($years < 10) $tenureBuckets['5-10 yr']++;
                else $tenureBuckets['10+ yr']++;
            }
        });

        // ── Chart 10: Performance rating distribution (radar / column) ──
        $ratingBuckets = Appraisal::select('rating', DB::raw('COUNT(*) as total'))
            ->whereNotNull('rating')
            ->groupBy('rating')
            ->pluck('total', 'rating')
            ->toArray();

        // ── Top hiring departments (this year, bar race style) ──────────
        $hiringDepts = Department::leftJoin('employees', function ($j) use ($today) {
            $j->on('employees.department_id', '=', 'departments.id')
                ->whereYear('employees.joining_date', $today->year);
        })
            ->select('departments.name', DB::raw('COUNT(employees.id) as hires'))
            ->groupBy('departments.id', 'departments.name')
            ->orderByDesc('hires')
            ->limit(6)
            ->get();

        // Recent activity data
        $recentJoiners = Employee::with(['department', 'designation'])
            ->whereNotNull('joining_date')
            ->orderByDesc('joining_date')
            ->limit(5)
            ->get();

        $upcomingBirthdays = Employee::whereNotNull('date_of_birth')
            ->where('status', 'active')
            ->get()
            ->map(function ($e) {
                $dob = Carbon::parse($e->date_of_birth);
                $next = $dob->copy()->year(now()->year);
                if ($next->lt(now()->startOfDay())) $next->addYear();
                $e->next_birthday = $next;
                $e->days_until = now()->startOfDay()->diffInDays($next, false);
                return $e;
            })
            ->filter(fn ($e) => $e->days_until >= 0 && $e->days_until <= 30)
            ->sortBy('days_until')
            ->take(6)
            ->values();

        $recentPendingLeaves = LeaveRequest::with(['employee', 'leaveType'])
            ->where('status', 'pending')
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.hr.dashboard', compact(
            'totalEmployees', 'activeEmployees', 'onProbation', 'onNotice',
            'newThisMonth', 'exitsThisMonth',
            'presentToday', 'absentToday', 'lateToday', 'halfDayToday', 'onLeaveToday',
            'attendanceRate', 'pendingLeaves', 'activeWarnings', 'pendingPenalties',
            'payrollThisMonth', 'payslipsPaid', 'payslipsTotal',
            'headcountTrend', 'deptHeadcount', 'genderSplit', 'employmentType',
            'attendance30', 'leavesByType', 'payrollTrend',
            'ageBuckets', 'tenureBuckets', 'ratingBuckets', 'hiringDepts',
            'recentJoiners', 'upcomingBirthdays', 'recentPendingLeaves'
        ));
    }
}
