<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRegularization;
use App\Models\Shift;
use App\Services\AttendanceRegularizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RegularizationController extends Controller
{
    public function __construct(private AttendanceRegularizationService $service) {}

    public function index()
    {
        $employee = Auth::guard('employee')->user();
        $requests = AttendanceRegularization::where('employee_id', $employee->id)
            ->with('reviewer')
            ->latest()
            ->paginate(15);

        return view('employee.regularizations.index', compact('requests'));
    }

    public function create(Request $request)
    {
        // Show the employee their own shift on the form. Most bogus requests
        // are people guessing at times they were never expected to work.
        $employee = Auth::guard('employee')->user();

        return view('employee.regularizations.create', [
            'date' => $request->input('date'),
            'shift' => $employee?->shift_id
                ? Shift::withoutGlobalScopes()->find($employee->shift_id)
                : null,
        ]);
    }

    public function store(Request $request)
    {
        $employee = Auth::guard('employee')->user();
        $data = $request->validate([
            'date' => ['required', 'date', 'before_or_equal:today'],
            'request_type' => ['required', 'in:missed_punch,wrong_punch,forgot_checkout,missed_day,other'],
            'expected_in' => ['nullable', 'date_format:H:i'],
            // Out-before-in is almost always a 12-hour mix-up (typing 05:35
            // for 5:35 PM). Only an overnight shift can legitimately end
            // before it starts, so allow it just for those employees.
            'expected_out' => ['nullable', 'date_format:H:i', function ($attr, $value, $fail) use ($request) {
                $in = $request->input('expected_in');
                if (! $value || ! $in || $value > $in) {
                    return;
                }

                $employee = Auth::guard('employee')->user();
                $shift = $employee?->shift_id
                    ? Shift::withoutGlobalScopes()->find($employee->shift_id)
                    : null;
                $isOvernight = $shift && $shift->start_time && $shift->end_time
                    && substr((string) $shift->end_time, 0, 5) <= substr((string) $shift->start_time, 0, 5);

                if (! $isOvernight) {
                    $fail('Check-out time must be after check-in time. If you left in the evening, pick the PM time (e.g. 05:35 PM, not 05:35 AM).');
                }
            }],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        // One open request per day.
        $dup = AttendanceRegularization::where('employee_id', $employee->id)
            ->whereDate('date', $data['date'])
            ->where('status', 'pending')
            ->exists();
        if ($dup) {
            return back()->withErrors(['date' => 'You already have a pending request for this date.'])->withInput();
        }

        $data['employee_id'] = $employee->id;
        $data['business_id'] = $employee->business_id;
        $this->service->create($data);

        return redirect()->route('employee.regularizations.index')
            ->with('success', 'Correction request submitted. HR will review it within the resolution window.');
    }

    public function cancel(AttendanceRegularization $regularization)
    {
        $employee = Auth::guard('employee')->user();
        abort_unless($regularization->employee_id === $employee->id, 403);

        if ($regularization->status !== 'pending') {
            return back()->withErrors(['status' => 'Only pending requests can be cancelled.']);
        }
        $regularization->update(['status' => 'cancelled']);

        return back()->with('success', 'Request cancelled.');
    }
}
