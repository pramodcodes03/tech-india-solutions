<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Exports\GenericArrayExport;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\Payslip;
use App\Models\ReimbursementClaim;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function __construct(private AttendanceService $attendance)
    {
    }

    private function guard(): void
    {
        abort_unless(Auth::guard('admin')->user()->can('reports.view'), 403);
    }

    public function index()
    {
        $this->guard();

        return view('admin.hr.reports.index');
    }

    /** Employee Master — filterable, exportable. */
    public function employeeMaster(Request $request)
    {
        $this->guard();
        $query = Employee::with(['department', 'designation'])
            ->when($request->department_id, fn ($q, $v) => $q->where('department_id', $v))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->orderBy('first_name');

        if ($request->get('export') === 'excel') {
            $rows = $query->get()->map(fn (Employee $e) => [
                $e->employee_code, $e->full_name, $e->email, $e->phone,
                $e->department?->name, $e->designation?->name,
                optional($e->joining_date)->format('Y-m-d'), $e->status,
                $e->bank_account_number, $e->bank_ifsc, $e->esi_number, $e->uan_number,
            ])->all();

            return Excel::download(new GenericArrayExport(
                ['Code', 'Name', 'Email', 'Phone', 'Department', 'Designation', 'Joining', 'Status', 'Bank A/c', 'IFSC', 'ESI No.', 'UAN'],
                $rows
            ), 'employee-master-'.date('Y-m-d').'.xlsx');
        }

        $employees = $query->paginate(25)->withQueryString();
        $departments = Department::orderBy('name')->get();

        return view('admin.hr.reports.employee-master', compact('employees', 'departments'));
    }

    /** Payroll — monthly summary, department-wise, component-wise. */
    public function payroll(Request $request)
    {
        $this->guard();
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        $payslips = Payslip::with('employee.department')->where('month', $month)->where('year', $year)->get();

        $byDept = $payslips->groupBy(fn ($p) => $p->employee?->department?->name ?? 'Unassigned')
            ->map(fn ($g) => ['count' => $g->count(), 'gross' => $g->sum('gross_earnings'), 'net' => $g->sum('net_pay')]);

        $components = [
            'Basic' => $payslips->sum('basic'), 'HRA' => $payslips->sum('hra'),
            'Conveyance' => $payslips->sum('conveyance'), 'Medical' => $payslips->sum('medical'),
            'Special' => $payslips->sum('special'), 'Other' => $payslips->sum('other_allowance'),
            'Bonus' => $payslips->sum('bonus'), 'PF' => $payslips->sum('pf'), 'ESI' => $payslips->sum('esi'),
            'PT' => $payslips->sum('professional_tax'), 'TDS' => $payslips->sum('tds'),
        ];

        $totals = ['gross' => $payslips->sum('gross_earnings'), 'deductions' => $payslips->sum('total_deductions'), 'net' => $payslips->sum('net_pay'), 'count' => $payslips->count()];

        if ($request->get('export') === 'excel') {
            $rows = $payslips->map(fn (Payslip $p) => [
                $p->employee?->employee_code, $p->employee?->full_name, $p->employee?->department?->name,
                $p->gross_earnings, $p->total_deductions, $p->net_pay,
            ])->all();

            return Excel::download(new GenericArrayExport(
                ['Code', 'Name', 'Department', 'Gross', 'Deductions', 'Net'], $rows
            ), "payroll-{$year}-{$month}.xlsx");
        }

        return view('admin.hr.reports.payroll', compact('byDept', 'components', 'totals', 'month', 'year'));
    }

    /** Leave — balance / usage / carry-forward. */
    public function leave(Request $request)
    {
        $this->guard();
        $year = (int) $request->input('year', now()->year);

        $balances = LeaveBalance::with(['employee', 'leaveType'])->where('year', $year)->get();

        if ($request->get('export') === 'excel') {
            $rows = $balances->map(fn ($b) => [
                $b->employee?->employee_code, $b->employee?->full_name, $b->leaveType?->name,
                $b->allocated, $b->carried_forward, $b->used, $b->pending, $b->available,
            ])->all();

            return Excel::download(new GenericArrayExport(
                ['Code', 'Name', 'Leave Type', 'Allocated', 'Carried Fwd', 'Used', 'Pending', 'Available'], $rows
            ), "leave-report-{$year}.xlsx");
        }

        $byType = $balances->groupBy(fn ($b) => $b->leaveType?->name ?? '—')
            ->map(fn ($g) => ['allocated' => $g->sum('allocated'), 'used' => $g->sum('used'), 'available' => $g->sum(fn ($b) => $b->available)]);

        return view('admin.hr.reports.leave', compact('balances', 'byType', 'year'));
    }

    /** Attendance — per-employee monthly summary (present/absent/late/OT). */
    public function attendance(Request $request)
    {
        $this->guard();
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        $employees = Employee::whereIn('status', ['active', 'probation', 'on_notice'])->orderBy('first_name')->get();
        $rows = $employees->map(function ($e) use ($month, $year) {
            $s = $this->attendance->monthlySummary($e->id, $month, $year);

            return [
                'employee' => $e,
                'present' => $s['present'] ?? 0,
                'absent' => $s['absent'] ?? 0,
                'half_day' => $s['half_day'] ?? 0,
                'leave' => $s['on_leave'] ?? ($s['leave'] ?? 0),
                'late' => $s['late'] ?? 0,
                'paid_days' => $s['paid_days'] ?? 0,
            ];
        });

        if ($request->get('export') === 'excel') {
            $export = $rows->map(fn ($r) => [
                $r['employee']->employee_code, $r['employee']->full_name,
                $r['present'], $r['absent'], $r['half_day'], $r['leave'], $r['late'], $r['paid_days'],
            ])->all();

            return Excel::download(new GenericArrayExport(
                ['Code', 'Name', 'Present', 'Absent', 'Half-day', 'Leave', 'Late', 'Paid Days'], $export
            ), "attendance-report-{$year}-{$month}.xlsx");
        }

        return view('admin.hr.reports.attendance', compact('rows', 'month', 'year'));
    }

    /** Expense claims — by claim / employee / category. */
    public function expenseClaims(Request $request)
    {
        $this->guard();

        $byCategory = ReimbursementClaim::with('category')
            ->select('expense_category_id', DB::raw('count(*) as c'), DB::raw('sum(amount) as amt'),
                DB::raw("sum(case when status in ('approved','disbursed') then amount else 0 end) as approved_amt"))
            ->groupBy('expense_category_id')->get();

        $byEmployee = ReimbursementClaim::with('employee')
            ->select('employee_id', DB::raw('count(*) as c'), DB::raw('sum(amount) as amt'))
            ->groupBy('employee_id')->orderByDesc('amt')->limit(25)->get();

        return view('admin.hr.reports.expense-claims', compact('byCategory', 'byEmployee'));
    }
}
