<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Services\EmployeeService;
use App\Services\LeaveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LeaveBalanceController extends Controller
{
    public function __construct(
        protected LeaveService $leaveService,
        protected EmployeeService $employeeService,
    ) {}

    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('leaves.view'), 403);

        $year = (int) $request->input('year', now()->year);

        $employees = Employee::with(['department', 'designation'])
            ->whereIn('status', ['active', 'probation', 'on_notice'])
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('first_name', 'like', "%{$s}%")
                    ->orWhere('last_name', 'like', "%{$s}%")
                    ->orWhere('employee_code', 'like', "%{$s}%");
            }))
            ->when($request->department_id, fn ($q, $id) => $q->where('department_id', $id))
            ->orderBy('first_name')
            ->paginate(15)
            ->withQueryString();

        $types = LeaveType::where('status', 'active')->where('is_paid', true)->orderBy('code')->get();

        // Pull balances for all shown employees in one query
        $empIds = $employees->pluck('id')->all();
        $balances = LeaveBalance::whereIn('employee_id', $empIds)
            ->where('year', $year)
            ->get()
            ->groupBy('employee_id');

        $departments = Department::where('status', 'active')->orderBy('name')->get();

        return view('admin.hr.leave-balances.index', compact('employees', 'types', 'balances', 'year', 'departments'));
    }

    /**
     * Show/edit balances for a single employee.
     */
    public function edit(Employee $employee, Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('leaves.view'), 403);
        $year = (int) $request->input('year', now()->year);

        $types = LeaveType::where('status', 'active')->where('is_paid', true)->orderBy('code')->get();

        $balances = LeaveBalance::where('employee_id', $employee->id)
            ->where('year', $year)
            ->get()
            ->keyBy('leave_type_id');

        return view('admin.hr.leave-balances.edit', compact('employee', 'year', 'types', 'balances'));
    }

    public function update(Request $request, Employee $employee)
    {
        abort_unless(Auth::guard('admin')->user()->can('leaves.approve'), 403);

        $data = $request->validate([
            'year' => ['required', 'integer', 'between:2020,2100'],
            'balances' => ['required', 'array'],
            'balances.*.allocated' => ['nullable', 'numeric', 'min:0'],
            'balances.*.carried_forward' => ['nullable', 'numeric', 'min:0'],
        ]);

        $year = (int) $data['year'];

        DB::transaction(function () use ($employee, $year, $data) {
            foreach ($data['balances'] as $leaveTypeId => $fields) {
                $this->leaveService->setBalance(
                    $employee->id,
                    (int) $leaveTypeId,
                    $year,
                    [
                        'allocated' => isset($fields['allocated']) ? (float) $fields['allocated'] : 0,
                        'carried_forward' => isset($fields['carried_forward']) ? (float) $fields['carried_forward'] : 0,
                    ]
                );
            }
        });

        return redirect()
            ->route('admin.hr.leave-balances.edit', ['employee' => $employee, 'year' => $year])
            ->with('success', "Leave balances for {$year} saved.");
    }

    /**
     * Bulk-allocate (provision) leave balances for all active employees for a year.
     * Uses each leave type's annual_quota, prorated by joining date.
     */
    public function bulkAllocate(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('leaves.approve'), 403);

        $data = $request->validate([
            'year' => ['required', 'integer', 'between:2020,2100'],
        ]);
        $year = (int) $data['year'];

        $employees = Employee::whereIn('status', ['active', 'probation', 'on_notice'])->get();
        $count = 0;
        foreach ($employees as $emp) {
            $this->employeeService->allocateAnnualLeaves($emp, $year);
            $count++;
        }

        return redirect()
            ->route('admin.hr.leave-balances.index', ['year' => $year])
            ->with('success', "Allocated {$year} leave balances for {$count} employees.");
    }
}
