<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShiftController extends Controller
{
    public function index()
    {
        abort_unless(Auth::guard('admin')->user()->can('shifts.view'), 403);
        $shifts = Shift::withCount('employees')->orderBy('start_time')->paginate(20);

        return view('admin.hr.shifts.index', compact('shifts'));
    }

    public function create()
    {
        abort_unless(Auth::guard('admin')->user()->can('shifts.create'), 403);

        return view('admin.hr.shifts.create');
    }

    public function store(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('shifts.create'), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'grace_minutes' => ['required', 'integer', 'min:0', 'max:120'],
            'half_day_after_minutes' => ['required', 'integer', 'min:30'],
            'status' => ['required', 'in:active,inactive'],
        ]);
        Shift::create($data);

        return redirect()->route('admin.hr.shifts.index')->with('success', 'Shift created.');
    }

    public function edit(Shift $shift)
    {
        abort_unless(Auth::guard('admin')->user()->can('shifts.edit'), 403);

        return view('admin.hr.shifts.edit', compact('shift'));
    }

    public function update(Request $request, Shift $shift)
    {
        abort_unless(Auth::guard('admin')->user()->can('shifts.edit'), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'grace_minutes' => ['required', 'integer', 'min:0', 'max:120'],
            'half_day_after_minutes' => ['required', 'integer', 'min:30'],
            'status' => ['required', 'in:active,inactive'],
        ]);
        $shift->update($data);

        return redirect()->route('admin.hr.shifts.index')->with('success', 'Shift updated.');
    }

    public function destroy(Shift $shift)
    {
        abort_unless(Auth::guard('admin')->user()->can('shifts.delete'), 403);
        $shift->delete();

        return back()->with('success', 'Shift deleted.');
    }
}
