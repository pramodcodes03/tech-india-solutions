<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('departments.view'), 403);

        $departments = Department::with('head')
            ->withCount('employees')
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%");
            }))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.hr.departments.index', compact('departments'));
    }

    public function create()
    {
        abort_unless(Auth::guard('admin')->user()->can('departments.create'), 403);
        $employees = Employee::whereIn('status', ['active', 'probation'])->orderBy('first_name')->get();

        return view('admin.hr.departments.create', compact('employees'));
    }

    public function store(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('departments.create'), 403);
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:departments,code'],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'head_id' => ['nullable', 'exists:employees,id'],
            'status' => ['required', 'in:active,inactive'],
        ]);
        $data['created_by'] = Auth::guard('admin')->id();
        Department::create($data);

        return redirect()->route('admin.hr.departments.index')->with('success', 'Department created.');
    }

    public function edit(Department $department)
    {
        abort_unless(Auth::guard('admin')->user()->can('departments.edit'), 403);
        $employees = Employee::whereIn('status', ['active', 'probation'])->orderBy('first_name')->get();

        return view('admin.hr.departments.edit', compact('department', 'employees'));
    }

    public function update(Request $request, Department $department)
    {
        abort_unless(Auth::guard('admin')->user()->can('departments.edit'), 403);
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:departments,code,'.$department->id],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'head_id' => ['nullable', 'exists:employees,id'],
            'status' => ['required', 'in:active,inactive'],
        ]);
        $data['updated_by'] = Auth::guard('admin')->id();
        $department->update($data);

        return redirect()->route('admin.hr.departments.index')->with('success', 'Department updated.');
    }

    public function destroy(Department $department)
    {
        abort_unless(Auth::guard('admin')->user()->can('departments.delete'), 403);
        $department->update(['deleted_by' => Auth::guard('admin')->id()]);
        $department->delete();

        return back()->with('success', 'Department deleted.');
    }
}
