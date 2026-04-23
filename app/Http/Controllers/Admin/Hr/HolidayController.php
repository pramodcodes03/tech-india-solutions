<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HolidayController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('holidays.view'), 403);
        $year = (int) ($request->input('year', date('Y')));

        $holidays = Holiday::whereYear('date', $year)->orderBy('date')->paginate(50)->withQueryString();
        $years = range((int) date('Y') + 1, (int) date('Y') - 3);

        return view('admin.hr.holidays.index', compact('holidays', 'year', 'years'));
    }

    public function create()
    {
        abort_unless(Auth::guard('admin')->user()->can('holidays.create'), 403);

        return view('admin.hr.holidays.create');
    }

    public function store(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('holidays.create'), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'date' => ['required', 'date', 'unique:holidays,date'],
            'type' => ['required', 'in:public,optional,restricted'],
            'description' => ['nullable', 'string'],
        ]);
        $data['created_by'] = Auth::guard('admin')->id();
        Holiday::create($data);

        return redirect()->route('admin.hr.holidays.index')->with('success', 'Holiday added.');
    }

    public function edit(Holiday $holiday)
    {
        abort_unless(Auth::guard('admin')->user()->can('holidays.edit'), 403);

        return view('admin.hr.holidays.edit', compact('holiday'));
    }

    public function update(Request $request, Holiday $holiday)
    {
        abort_unless(Auth::guard('admin')->user()->can('holidays.edit'), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'date' => ['required', 'date', 'unique:holidays,date,'.$holiday->id],
            'type' => ['required', 'in:public,optional,restricted'],
            'description' => ['nullable', 'string'],
        ]);
        $holiday->update($data);

        return redirect()->route('admin.hr.holidays.index')->with('success', 'Holiday updated.');
    }

    public function destroy(Holiday $holiday)
    {
        abort_unless(Auth::guard('admin')->user()->can('holidays.delete'), 403);
        $holiday->delete();

        return back()->with('success', 'Holiday removed.');
    }
}
