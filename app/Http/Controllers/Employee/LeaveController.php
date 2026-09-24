<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\BusinessWeekOff;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Notifications\NotificationDispatcher;
use App\Services\LeaveBalanceGateService;
use App\Services\LeaveEligibilityService;
use App\Services\LeaveService;
use App\Support\HrSettings;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LeaveController extends Controller
{
    public function __construct(protected LeaveService $service) {}

    public function index()
    {
        $employee = Auth::guard('employee')->user();

        $requests = LeaveRequest::where('employee_id', $employee->id)
            ->with('leaveType', 'approver', 'splits.leaveType')
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
        $eligibility = app(LeaveEligibilityService::class);
        $leaveEligibility = $types->mapWithKeys(fn ($t) => [
            $t->id => $eligibility->evaluate($employee, $t),
        ]);

        // Week-off weekdays (0=Sun..6=Sat) + public-holiday dates so the form's
        // "Days requested" preview excludes them — matching the server count.
        // Must be the SAME rule the server counts days with, or the form
        // refuses a request the server would have accepted.
        $weekOffDays = collect(BusinessWeekOff::offDaysFor($employee->business_id));

        $holidayDates = Holiday::withoutGlobalScopes()
            ->where('business_id', $employee->business_id)
            ->where('is_dynamic', false)
            ->whereIn(DB::raw('YEAR(date)'), [now()->year, now()->year + 1])
            ->get()
            ->map(fn ($h) => $h->date->toDateString())
            ->values();

        // Leave Balance Gate state, so the form can refuse a request the server
        // would refuse anyway — and say why before the employee writes a reason.
        $gate = app(LeaveBalanceGateService::class);
        $balanceGate = [
            'enabled' => $gate->isEnabled($employee->business_id),
            'lwp_exception' => $gate->lwpExceptionAllowed($employee->business_id),
            'has_any_paid_balance' => $gate->hasAnyPaidBalance($employee, now()->year),
        ];

        // Combination Leave is a per-business switch; when it is off the
        // employee never sees the option and the server refuses splits anyway.
        $combinationEnabled = HrSettings::boolForBusiness(
            'leave_combination_enabled', $employee->business_id, true,
        );

        return view('employee.leaves.create', compact(
            'types', 'balances', 'weekOffDays', 'holidayDates', 'leaveEligibility',
            'balanceGate', 'combinationEnabled',
        ));
    }

    public function store(Request $request)
    {
        $employee = Auth::guard('employee')->user();

        // The combine rows live in the form whether or not combining is on, so
        // an ordinary application arrives carrying blank ones. Drop anything
        // not actually filled in before validating — otherwise
        // required_with:splits rejects a perfectly good single-type request,
        // which is what "cannot apply for half-day leave" turned out to be.
        $request->merge([
            'splits' => collect($request->input('splits', []))
                ->filter(fn ($row) => filled($row['leave_type_id'] ?? null)
                    && (float) ($row['days'] ?? 0) > 0)
                ->values()
                ->all() ?: null,
        ]);

        $data = $request->validate([
            // Combined Leave posts splits instead of a single type; the primary
            // type is derived from the largest contributor in LeaveService.
            'leave_type_id' => ['required_without:splits', 'nullable', 'exists:leave_types,id'],
            'splits' => ['nullable', 'array', 'min:2'],
            'splits.*.leave_type_id' => ['required_with:splits', 'exists:leave_types,id'],
            'splits.*.days' => ['required_with:splits', 'numeric', 'min:0'],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'day_portion' => ['required', 'in:full,first_half,second_half'],
            'reason' => ['required', 'string', 'min:5'],
        ], [
            'leave_type_id.required_without' => 'Please choose a leave type.',
            'splits.min' => 'A combined request needs at least two leave types.',
        ]);

        // Combination Leave rules, enforced here rather than only in the form:
        // a hidden control is not a rule, and both of these change what gets
        // written to the ledger.
        if (! empty($data['splits'])) {
            if (! HrSettings::boolForBusiness('leave_combination_enabled', $employee->business_id, true)) {
                return back()->withInput()->with('error',
                    'Combination Leave is turned off for this business. Please apply using a single leave type.');
            }

            // Half a day cannot be split across two types — the option is only
            // ever offered on a full day.
            if (($data['day_portion'] ?? 'full') !== 'full'
                && $data['from_date'] === $data['to_date']) {
                return back()->withInput()->with('error',
                    'Combination Leave applies to full-day requests only. Choose Full Day, or apply for the half day using a single leave type.');
            }

            // A combined request drives leave_type_id itself; drop any stale
            // value the form left behind so the service picks the largest
            // contributor.
            unset($data['leave_type_id']);
        }

        // Backdated-application restriction: applications for a date older than
        // the configurable window (default 72h) are auto-rejected up front.
        $windowHours = HrSettings::int('leave_application_window_hours', 72);
        $earliest = now()->subHours($windowHours)->startOfDay();
        if (Carbon::parse($data['from_date'])->startOfDay()->lt($earliest)) {
            return back()->withInput()->with('error',
                "Leave cannot be applied for dates older than {$windowHours} hours. Please contact HR for older corrections.");
        }

        $data['employee_id'] = $employee->id;

        try {
            $leaveRequest = $this->service->submit($data);
            NotificationDispatcher::fire(
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

        try {
            $this->service->cancel($leaveRequest);
        } catch (\RuntimeException $e) {
            // Approved leave is HR's to reverse, not the employee's.
            return back()->with('error', $e->getMessage());
        }

        NotificationDispatcher::fire(
            'leave.cancelled',
            $leaveRequest->loadMissing('employee.reportingManager', 'leaveType'),
        );

        return back()->with('success', 'Leave request cancelled.');
    }

    public function show(LeaveRequest $leaveRequest)
    {
        $employee = Auth::guard('employee')->user();
        abort_unless($leaveRequest->employee_id === $employee->id, 403);
        $leaveRequest->load('leaveType', 'approver', 'splits.leaveType');

        return view('employee.leaves.show', ['request' => $leaveRequest]);
    }

    /**
     * Company leave-policy document (admin-configurable) shown to the employee.
     */
    public function policy()
    {
        $policy = HrSettings::get('leave_policy_document', '');

        return view('employee.leaves.policy', compact('policy'));
    }
}
