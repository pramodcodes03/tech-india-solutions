<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEmployeeRequest;
use App\Http\Requests\Admin\UpdateEmployeeRequest;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Shift;
use App\Services\EmployeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeController extends Controller
{
    public function __construct(protected EmployeeService $service) {}

    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('employees.view'), 403);

        $employees = Employee::with(['department', 'designation', 'shift'])
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('first_name', 'like', "%{$s}%")
                    ->orWhere('last_name', 'like', "%{$s}%")
                    ->orWhere('employee_code', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%");
            }))
            ->when($request->department_id, fn ($q, $id) => $q->where('department_id', $id))
            ->when($request->designation_id, fn ($q, $id) => $q->where('designation_id', $id))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $departments = Department::where('status', 'active')->orderBy('name')->get();
        $designations = Designation::where('status', 'active')->orderBy('name')->get();

        return view('admin.hr.employees.index', compact('employees', 'departments', 'designations'));
    }

    public function create()
    {
        abort_unless(Auth::guard('admin')->user()->can('employees.create'), 403);

        $departments = Department::where('status', 'active')->orderBy('name')->get();
        $designations = Designation::where('status', 'active')->orderBy('name')->get();
        $shifts = Shift::where('status', 'active')->orderBy('name')->get();
        $managers = Employee::whereIn('status', ['active', 'probation'])->orderBy('first_name')->get();

        return view('admin.hr.employees.create', compact('departments', 'designations', 'shifts', 'managers'));
    }

    public function store(StoreEmployeeRequest $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('employees.create'), 403);

        $employee = $this->service->create($request->validated());

        return redirect()->route('admin.hr.employees.show', $employee)
            ->with('success', "Employee {$employee->employee_code} created. Default password: {$employee->employee_code}");
    }

    public function show(Employee $employee)
    {
        abort_unless(Auth::guard('admin')->user()->can('employees.view'), 403);

        $employee->load([
            'department', 'designation', 'shift', 'reportingManager',
            'currentSalary', 'documents',
            'warnings' => fn ($q) => $q->latest()->limit(5),
            'penalties' => fn ($q) => $q->latest()->limit(5),
            'leaveBalances.leaveType',
        ]);

        $recentPayslips = $employee->payslips()->latest()->limit(6)->get();
        $recentAttendance = $employee->attendance()->latest('date')->limit(15)->get();
        $incrementHistory = $employee->appraisals()
            ->whereIn('status', ['finalized', 'shared', 'acknowledged'])
            ->orderBy('effective_from', 'desc')
            ->orderBy('period_end', 'desc')
            ->get();

        return view('admin.hr.employees.show', compact('employee', 'recentPayslips', 'recentAttendance', 'incrementHistory'));
    }

    public function edit(Employee $employee)
    {
        abort_unless(Auth::guard('admin')->user()->can('employees.edit'), 403);

        $departments = Department::where('status', 'active')->orderBy('name')->get();
        $designations = Designation::where('status', 'active')->orderBy('name')->get();
        $shifts = Shift::where('status', 'active')->orderBy('name')->get();
        $managers = Employee::whereIn('status', ['active', 'probation'])
            ->where('id', '!=', $employee->id)
            ->orderBy('first_name')->get();

        return view('admin.hr.employees.edit', compact('employee', 'departments', 'designations', 'shifts', 'managers'));
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee)
    {
        abort_unless(Auth::guard('admin')->user()->can('employees.edit'), 403);

        $oldShiftId = $employee->shift_id;
        $this->service->update($employee, $request->validated());

        // Notify the employee if their shift changed.
        $fresh = $employee->fresh();
        if ($fresh->shift_id !== $oldShiftId && $fresh->shift_id) {
            $shift = $fresh->shift;
            \App\Notifications\NotificationDispatcher::fire(
                'shift.changed',
                $fresh,
                [
                    'shift_name' => $shift->name ?? null,
                    'start_time' => $shift->start_time ?? null,
                    'end_time' => $shift->end_time ?? null,
                    'effective_from' => now()->toDateString(),
                ],
            );
        }

        return redirect()->route('admin.hr.employees.show', $employee)
            ->with('success', 'Employee updated.');
    }

    public function destroy(Employee $employee)
    {
        abort_unless(Auth::guard('admin')->user()->can('employees.delete'), 403);

        $this->service->delete($employee);

        return redirect()->route('admin.hr.employees.index')
            ->with('success', 'Employee deactivated.');
    }

    public function resetPassword(Employee $employee)
    {
        abort_unless(Auth::guard('admin')->user()->can('employees.edit'), 403);

        $password = $this->service->resetPassword($employee);

        return back()->with('success', "Password reset. New password is: {$password}");
    }
}
