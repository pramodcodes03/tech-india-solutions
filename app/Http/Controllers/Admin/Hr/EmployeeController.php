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
    /** Allowed page sizes for the employee list. */
    private const PAGE_SIZES = [15, 50, 100, 200];

    public function __construct(protected EmployeeService $service) {}

    /** Resolve a safe per-page size from the request (defaults to 15). */
    private function perPage(Request $request): int
    {
        $pp = (int) $request->input('per_page', 15);

        return in_array($pp, self::PAGE_SIZES, true) ? $pp : 15;
    }

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
            ->paginate($this->perPage($request))
            ->withQueryString();

        $departments = Department::where('status', 'active')->orderBy('name')->get();
        $designations = Designation::where('status', 'active')->orderBy('name')->get();
        $shifts = Shift::where('status', 'active')->orderBy('name')->get();
        $managers = Employee::whereIn('status', ['active', 'probation'])->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'employee_code']);
        $pageSizes = self::PAGE_SIZES;
        $perPage = $this->perPage($request);

        return view('admin.hr.employees.index', compact('employees', 'departments', 'designations', 'shifts', 'managers', 'pageSizes', 'perPage'));
    }

    /**
     * Bulk-edit dropdown fields on selected employees. Only the fields the user
     * actually chose a value for are applied; the rest are left untouched.
     */
    public function bulkAction(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('employees.edit'), 403);

        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'designation_id' => ['nullable', 'exists:designations,id'],
            'shift_id' => ['nullable', 'exists:shifts,id'],
            'reporting_manager_id' => ['nullable', 'exists:employees,id'],
            'employment_type' => ['nullable', 'in:full_time,part_time,contract,intern'],
            'work_mode' => ['nullable', 'in:on_site,remote,hybrid'],
            'status' => ['nullable', 'in:active,probation,on_notice,terminated,resigned,absconded,inactive'],
        ]);

        // Collect only the dropdowns that were given a value.
        $fields = ['department_id', 'designation_id', 'shift_id', 'reporting_manager_id', 'employment_type', 'work_mode', 'status'];
        $changes = [];
        foreach ($fields as $f) {
            if ($request->filled($f)) {
                $changes[$f] = $request->input($f);
            }
        }

        if (empty($changes)) {
            return back()->with('warning', 'No fields were chosen to update.');
        }

        $employees = Employee::whereIn('id', $request->ids)->get();
        if ($employees->isEmpty()) {
            return back()->with('warning', 'No matching employees found.');
        }

        // Guard: an employee cannot be set as their own reporting manager.
        $managerId = $changes['reporting_manager_id'] ?? null;

        $count = 0;
        foreach ($employees as $employee) {
            $apply = $changes;
            if ($managerId && (int) $managerId === $employee->id) {
                unset($apply['reporting_manager_id']); // skip self-manager for this one
            }
            if (! empty($apply)) {
                $employee->fill($apply)->save();
                $count++;
            }
        }

        return back()->with('success', "Updated ".count($changes)." field(s) on {$count} employee(s).");
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

    public function destroy(Request $request, Employee $employee)
    {
        abort_unless(Auth::guard('admin')->user()->can('employees.delete'), 403);

        $request->validate([
            'confirm_code' => "required|string|in:{$employee->employee_code}",
        ], [
            'confirm_code.in' => 'Confirmation code did not match the employee code.',
        ]);

        $code = $employee->employee_code;
        $this->service->hardDelete($employee);

        return redirect()->route('admin.hr.employees.index')
            ->with('success', "Employee {$code} and all related data permanently deleted.");
    }

    public function toggleStatus(Employee $employee)
    {
        abort_unless(Auth::guard('admin')->user()->can('employees.edit'), 403);

        $employee = $this->service->toggleStatus($employee);

        return back()->with('success', "Employee marked {$employee->status}.");
    }

    public function resetPassword(Employee $employee)
    {
        abort_unless(Auth::guard('admin')->user()->can('employees.edit'), 403);

        $password = $this->service->resetPassword($employee);

        return back()->with('success', "Password reset. New password is: {$password}");
    }
}
