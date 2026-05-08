<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\BusinessWeekOff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WeekOffController extends Controller
{
    public function index()
    {
        abort_unless(Auth::guard('admin')->user()->can('holidays.view'), 403);

        $config = BusinessWeekOff::all()->keyBy('day_of_week');
        $days   = BusinessWeekOff::$dayNames;

        // Build stats
        $offDays     = $config->where('is_off', true)->count();
        $workingDays = 7 - $offDays;

        return view('admin.hr.week-off.index', compact('config', 'days', 'offDays', 'workingDays'));
    }

    public function save(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('holidays.edit'), 403);

        $days = [];
        foreach (range(0, 6) as $dow) {
            $days[$dow] = $request->boolean("day_{$dow}");
        }

        BusinessWeekOff::saveConfig($days);

        return redirect()->route('admin.hr.week-off.index')
            ->with('success', 'Week-off configuration saved successfully.');
    }
}
