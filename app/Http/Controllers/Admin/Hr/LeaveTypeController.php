<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\LeaveType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveTypeController extends Controller
{
    public function index()
    {
        abort_unless(Auth::guard('admin')->user()->can('leave_types.view'), 403);
        $types = LeaveType::orderBy('code')->paginate(20);

        return view('admin.hr.leave-types.index', compact('types'));
    }

    public function create()
    {
        abort_unless(Auth::guard('admin')->user()->can('leave_types.create'), 403);

        return view('admin.hr.leave-types.create');
    }

    public function store(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('leave_types.create'), 403);
        $data = $this->validated($request);
        LeaveType::create($data);

        return redirect()->route('admin.hr.leave-types.index')->with('success', 'Leave type created.');
    }

    public function edit(LeaveType $leaveType)
    {
        abort_unless(Auth::guard('admin')->user()->can('leave_types.edit'), 403);

        return view('admin.hr.leave-types.edit', compact('leaveType'));
    }

    public function update(Request $request, LeaveType $leaveType)
    {
        abort_unless(Auth::guard('admin')->user()->can('leave_types.edit'), 403);
        $data = $this->validated($request, $leaveType->id);
        $leaveType->update($data);

        return redirect()->route('admin.hr.leave-types.index')->with('success', 'Leave type updated.');
    }

    public function destroy(LeaveType $leaveType)
    {
        abort_unless(Auth::guard('admin')->user()->can('leave_types.delete'), 403);
        $leaveType->delete();

        return back()->with('success', 'Leave type removed.');
    }

    private function validated(Request $r, ?int $id = null): array
    {
        return $r->validate([
            'code' => ['required', 'string', 'max:10', 'unique:leave_types,code'.($id ? ','.$id : '')],
            'name' => ['required', 'string', 'max:100'],
            'annual_quota' => ['required', 'numeric', 'min:0'],
            'is_paid' => ['nullable', 'boolean'],
            'carry_forward' => ['nullable', 'boolean'],
            'max_carry_forward' => ['nullable', 'numeric', 'min:0'],
            'encashable' => ['nullable', 'boolean'],
            'color' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
        ]);
    }
}
