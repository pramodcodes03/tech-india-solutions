<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    public function __construct(protected AttendanceService $service) {}

    public function index(Request $request)
    {
        $employee = Auth::guard('employee')->user();
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        $records = Attendance::where('employee_id', $employee->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('date')
            ->get()
            ->keyBy(fn ($r) => $r->date->toDateString());

        $summary = $this->service->monthlySummary($employee->id, $month, $year);

        return view('employee.attendance.index', compact('records', 'summary', 'month', 'year'));
    }

    public function punch(Request $request)
    {
        $employee = Auth::guard('employee')->user();
        $today = now()->toDateString();
        $now = now()->format('H:i:s');

        $existing = Attendance::where('employee_id', $employee->id)->whereDate('date', $today)->first();

        if (! $existing) {
            // Check in
            $this->service->upsert([
                'employee_id' => $employee->id,
                'date' => $today,
                'check_in' => $now,
                'source' => 'web',
                'status' => 'present',
            ]);

            return back()->with('success', "Checked in at {$now}.");
        }

        if (! $existing->check_out) {
            // Check out
            $this->service->upsert([
                'employee_id' => $employee->id,
                'date' => $today,
                'check_in' => $existing->check_in?->format('H:i:s'),
                'check_out' => $now,
                'source' => $existing->source,
            ]);

            return back()->with('success', "Checked out at {$now}.");
        }

        return back()->with('error', 'You have already completed attendance for today.');
    }
}
