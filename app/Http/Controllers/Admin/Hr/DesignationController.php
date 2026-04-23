<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Designation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DesignationController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('designations.view'), 403);

        $designations = Designation::with('department')
            ->withCount('employees')
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->when($request->department_id, fn ($q, $id) => $q->where('department_id', $id))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $departments = Department::where('status', 'active')->orderBy('name')->get();

        return view('admin.hr.designations.index', compact('designations', 'departments'));
    }

    public function create()
    {
        abort_unless(Auth::guard('admin')->user()->can('designations.create'), 403);
        $departments = Department::where('status', 'active')->orderBy('name')->get();

        return view('admin.hr.designations.create', compact('departments'));
    }

    public function store(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('designations.create'), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'level' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'in:active,inactive'],
        ]);
        $data['created_by'] = Auth::guard('admin')->id();
        Designation::create($data);

        return redirect()->route('admin.hr.designations.index')->with('success', 'Designation created.');
    }

    public function edit(Designation $designation)
    {
        abort_unless(Auth::guard('admin')->user()->can('designations.edit'), 403);
        $departments = Department::where('status', 'active')->orderBy('name')->get();

        return view('admin.hr.designations.edit', compact('designation', 'departments'));
    }

    public function update(Request $request, Designation $designation)
    {
        abort_unless(Auth::guard('admin')->user()->can('designations.edit'), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'level' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'in:active,inactive'],
        ]);
        $data['updated_by'] = Auth::guard('admin')->id();
        $designation->update($data);

        return redirect()->route('admin.hr.designations.index')->with('success', 'Designation updated.');
    }

    public function destroy(Designation $designation)
    {
        abort_unless(Auth::guard('admin')->user()->can('designations.delete'), 403);
        $designation->update(['deleted_by' => Auth::guard('admin')->id()]);
        $designation->delete();

        return back()->with('success', 'Designation deleted.');
    }
}
