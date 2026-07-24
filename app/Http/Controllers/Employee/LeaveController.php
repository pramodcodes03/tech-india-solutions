<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\LeaveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveController extends Controller
{
    public function __construct(protected LeaveService $service) {}

    public function index()
    {
        $employee = Auth::guard('employee')->user();

        $requests = LeaveRequest::where('employee_id', $employee->id)
            ->with('leaveType', 'approver')
            ->latest()
            ->paginate(15);

        $balances = $employee->leaveBalances()
            ->with('leaveType')
            ->where('year', now()->year)
            ->get();

        return view('employee.leaves.index', compact('requests', 'balances'));
    }

    public function create()
    {
        $employee = Auth::guard('employee')->user();
        $types = LeaveType::where('status', 'active')->orderBy('name')->get();
        $balances = $employee->leaveBalances()
            ->with('leaveType')
            ->where('year', now()->year)
            ->get()
            ->keyBy('leave_type_id');

        // Working-days eligibility per leave type (locked / unlocked + when it
        // unlocks), so the form can show it before the employee even applies.
        $eligibility = app(\App\Services\LeaveEligibilityService::class);
        $leaveEligibility = $types->mapWithKeys(fn ($t) => [
            $t->id => $eligibility->evaluate($employee, $t),
        ]);

        // Week-off weekdays (0=Sun..6=Sat) + public-holiday dates so the form's
        // "Days requested" preview excludes them — matching the server count.
        $weekOffDays = \App\Models\BusinessWeekOff::withoutGlobalScopes()
            ->where('business_id', $employee->business_id)
            ->where('is_off', true)
            ->pluck('day_of_week')
            ->map(fn ($d) => (int) $d)
            ->values();
        if ($weekOffDays->isEmpty()) {
            $weekOffDays = collect([0]); // default: Sunday off
        }

        $holidayDates = \App\Models\Holiday::withoutGlobalScopes()
            ->where('business_id', $employee->business_id)
            ->where('is_dynamic', false)
            ->whereIn(\Illuminate\Support\Facades\DB::raw('YEAR(date)'), [now()->year, now()->year + 1])
            ->get()
            ->map(fn ($h) => $h->date->toDateString())
            ->values();

        return view('employee.leaves.create', compact('types', 'balances', 'weekOffDays', 'holidayDates', 'leaveEligibility'));
    }

    public function store(Request $request)
    {
        $employee = Auth::guard('employee')->user();

        $data = $request->validate([
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'day_portion' => ['required', 'in:full,first_half,second_half'],
            'reason' => ['required', 'string', 'min:5'],
        ]);

        // Backdated-application restriction: applications for a date older than
        // the configurable window (default 72h) are auto-rejected up front.
        $windowHours = \App\Support\HrSettings::int('leave_application_window_hours', 72);
        $earliest = now()->subHours($windowHours)->startOfDay();
        if (\Carbon\Carbon::parse($data['from_date'])->startOfDay()->lt($earliest)) {
            return back()->withInput()->with('error',
                "Leave cannot be applied for dates older than {$windowHours} hours. Please contact HR for older corrections.");
        }

        $data['employee_id'] = $employee->id;

        try {
            $leaveRequest = $this->service->submit($data);
            \App\Notifications\NotificationDispatcher::fire(
                'leave.applied',
                $leaveRequest->loadMissing('employee.reportingManager', 'leaveType'),
            );
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('employee.leaves.index')->with('success', 'Leave request submitted for approval.');
    }

    public function cancel(LeaveRequest $leaveRequest)
    {
        $employee = Auth::guard('employee')->user();
        abort_unless($leaveRequest->employee_id === $employee->id, 403);

        $this->service->cancel($leaveRequest);

        \App\Notifications\NotificationDispatcher::fire(
            'leave.cancelled',
            $leaveRequest->loadMissing('employee.reportingManager', 'leaveType'),
        );

        return back()->with('success', 'Leave request cancelled.');
    }

    public function show(LeaveRequest $leaveRequest)
    {
        $employee = Auth::guard('employee')->user();
        abort_unless($leaveRequest->employee_id === $employee->id, 403);
        $leaveRequest->load('leaveType', 'approver');

        return view('employee.leaves.show', ['request' => $leaveRequest]);
    }

    /**
     * Company leave-policy document (admin-configurable) shown to the employee.
     */
    public function policy()
    {
        $policy = \App\Support\HrSettings::get('leave_policy_document', '');

        return view('employee.leaves.policy', compact('policy'));
    }
}
