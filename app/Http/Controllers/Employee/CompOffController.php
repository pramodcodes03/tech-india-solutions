<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\CompOffRequest;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CompOffController extends Controller
{
    public function __construct(protected AttendanceService $attendance) {}

    public function index()
    {
        $employee = Auth::guard('employee')->user();

        $compOffs = CompOffRequest::where('employee_id', $employee->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('employee.comp-off.index', compact('compOffs'));
    }

    public function store(Request $request)
    {
        $employee = Auth::guard('employee')->user();

        $data = $request->validate([
            'worked_on' => ['required', 'date', 'before_or_equal:today'],
            'comp_date' => ['required', 'date', 'after:worked_on'],
            'reason'    => ['nullable', 'string', 'max:255'],
        ]);

        // Eligibility: comp-off compensates an employee for working on a
        // configured week-off day. So worked_on must (a) be a week-off for
        // this employee's business, and (b) have an attendance row that
        // proves they actually came in — present / late / half_day. A blank
        // calendar day, an absent, or a leave doesn't earn a comp-off.
        if (! $this->attendance->isBusinessWeekOff($data['worked_on'], (int) $employee->business_id)) {
            throw ValidationException::withMessages([
                'worked_on' => 'Comp-off can only be claimed for a week-off day (e.g. Sunday).',
            ]);
        }

        $workedStatus = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $data['worked_on'])
            ->value('status');

        if (! in_array($workedStatus, ['present', 'late', 'half_day'], true)) {
            throw ValidationException::withMessages([
                'worked_on' => 'No present attendance found on this date — comp-off is only allowed if you actually worked on the week-off.',
            ]);
        }

        // Prevent claiming the same worked_on twice (any active request).
        $duplicate = CompOffRequest::where('employee_id', $employee->id)
            ->whereDate('worked_on', $data['worked_on'])
            ->whereIn('status', ['pending', 'approved'])
            ->exists();
        if ($duplicate) {
            throw ValidationException::withMessages([
                'worked_on' => 'You have already submitted a comp-off for this date.',
            ]);
        }

        $data['employee_id'] = $employee->id;
        $data['business_id'] = $employee->business_id;
        $data['status']      = 'pending';

        $req = CompOffRequest::create($data);

        \App\Notifications\NotificationDispatcher::fire(
            'comp_off.requested',
            $req->loadMissing('employee'),
        );

        return back()->with('success', 'Comp-off request submitted. Pending approval.');
    }

    public function cancel(CompOffRequest $compOff)
    {
        abort_unless($compOff->employee_id === Auth::guard('employee')->id(), 403);
        abort_unless($compOff->isPending(), 422);

        $compOff->update(['status' => 'cancelled']);

        return back()->with('success', 'Comp-off request cancelled.');
    }
}
