<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Holiday;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HolidayController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('holidays.view'), 403);
        $year = (int) ($request->input('year', date('Y')));

        // Expand yearly holidays into this year + fetch year-specific ones
        $holidays = Holiday::forYear($year)->sortBy('date');
        $years    = range((int) date('Y') + 1, (int) date('Y') - 3);

        // Stats
        $fixedCount   = $holidays->where('is_yearly', true)->count();
        $dynamicCount = $holidays->where('is_dynamic', true)->count();
        $regularCount = $holidays->where('is_yearly', false)->where('is_dynamic', false)->count();

        return view('admin.hr.holidays.index', compact('holidays', 'year', 'years', 'fixedCount', 'dynamicCount', 'regularCount'));
    }

    public function create()
    {
        abort_unless(Auth::guard('admin')->user()->can('holidays.create'), 403);
        // employees table has first_name + last_name (not a single 'name' column);
        // the model exposes a full_name accessor. Pull both name parts so the
        // view's $emp->full_name works without lazy-loading other columns.
        $employees = Employee::orderBy('first_name')->get(['id', 'first_name', 'last_name', 'employee_code']);

        return view('admin.hr.holidays.create', compact('employees'));
    }

    public function store(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('holidays.create'), 403);

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'date'        => ['required', 'date'],
            'type'        => ['required', 'in:public,optional,restricted'],
            'is_yearly'   => ['boolean'],
            'is_dynamic'  => ['boolean'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'description' => ['nullable', 'string'],
        ]);

        $data['is_yearly']   = $request->boolean('is_yearly');
        $data['is_dynamic']  = $request->boolean('is_dynamic');
        $data['created_by']  = Auth::guard('admin')->id();

        // For non-yearly holidays only enforce unique date
        if (! $data['is_yearly']) {
            $exists = Holiday::whereDate('date', $data['date'])
                ->where('is_yearly', false)
                ->when($data['is_dynamic'] && ! empty($data['employee_id']), fn ($q) => $q->where('employee_id', $data['employee_id']))
                ->exists();

            if ($exists) {
                return back()->withErrors(['date' => 'A holiday already exists on this date.'])->withInput();
            }
        }

        Holiday::create($data);

        return redirect()->route('admin.hr.holidays.index')->with('success', 'Holiday added successfully.');
    }

    public function edit(Holiday $holiday)
    {
        abort_unless(Auth::guard('admin')->user()->can('holidays.edit'), 403);
        // employees table has first_name + last_name (not a single 'name' column);
        // the model exposes a full_name accessor. Pull both name parts so the
        // view's $emp->full_name works without lazy-loading other columns.
        $employees = Employee::orderBy('first_name')->get(['id', 'first_name', 'last_name', 'employee_code']);

        return view('admin.hr.holidays.edit', compact('holiday', 'employees'));
    }

    public function update(Request $request, Holiday $holiday)
    {
        abort_unless(Auth::guard('admin')->user()->can('holidays.edit'), 403);

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'date'        => ['required', 'date'],
            'type'        => ['required', 'in:public,optional,restricted'],
            'is_yearly'   => ['boolean'],
            'is_dynamic'  => ['boolean'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'description' => ['nullable', 'string'],
        ]);

        $data['is_yearly']  = $request->boolean('is_yearly');
        $data['is_dynamic'] = $request->boolean('is_dynamic');

        $holiday->update($data);

        return redirect()->route('admin.hr.holidays.index')->with('success', 'Holiday updated successfully.');
    }

    public function destroy(Holiday $holiday)
    {
        abort_unless(Auth::guard('admin')->user()->can('holidays.delete'), 403);
        $holiday->delete();

        return back()->with('success', 'Holiday removed.');
    }
}
