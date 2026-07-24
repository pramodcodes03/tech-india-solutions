<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Services\LeaveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Reporting-Manager leave approvals from the employee portal.
 *
 * A "manager" here is an Employee that other employees report to (via
 * employees.reporting_manager_id). They see and action leave requests for
 * their direct reports — and nothing else. This mirrors the leave.applied
 * notification, which already goes to the reporting_manager.
 */
class TeamLeaveController extends Controller
{
    public function __construct(protected LeaveService $service) {}

    /** True if the current employee has at least one direct report. */
    private function isManager(int $employeeId): bool
    {
        return Employee::where('reporting_manager_id', $employeeId)->exists();
    }

    /** Block employees who manage no one from the whole controller. */
    private function authorizeManager(): Employee
    {
        $me = Auth::guard('employee')->user();
        abort_unless($this->isManager($me->id), 403, 'You have no direct reports.');

        return $me;
    }

    public function index(Request $request)
    {
        $me = $this->authorizeManager();

        $requests = LeaveRequest::with(['employee.department', 'leaveType', 'approver', 'approverEmployee'])
            ->whereHas('employee', fn ($q) => $q->where('reporting_manager_id', $me->id))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $counts = [
            'pending'  => $this->scopedCount($me->id, 'pending'),
            'approved' => $this->scopedCount($me->id, 'approved'),
            'rejected' => $this->scopedCount($me->id, 'rejected'),
        ];

        return view('employee.team-leaves.index', compact('requests', 'counts'));
    }

    public function approve(Request $request, LeaveRequest $leaveRequest)
    {
        $this->guardRequestInScope($leaveRequest);

        $data = $request->validate([
            'remarks' => ['nullable', 'string', 'max:500'],
            'paid_days' => ['nullable', 'numeric', 'min:0', 'max:'.$leaveRequest->days],
        ]);

        $paid = array_key_exists('paid_days', $data) && $data['paid_days'] !== null && $data['paid_days'] !== ''
            ? (float) $data['paid_days']
            : null;

        $this->service->approve(
            $leaveRequest,
            null, // not an admin approver
            $data['remarks'] ?? null,
            $paid,
            Auth::guard('employee')->id(), // manager (employee) approver
        );

        \App\Notifications\NotificationDispatcher::fire(
            'leave.approved',
            $leaveRequest->loadMissing('employee', 'leaveType'),
            ['remarks' => $data['remarks'] ?? null],
        );

        return back()->with('success', 'Leave request approved.');
    }

    public function reject(Request $request, LeaveRequest $leaveRequest)
    {
        $this->guardRequestInScope($leaveRequest);

        $data = $request->validate(['remarks' => ['required', 'string', 'min:3', 'max:500']]);

        $this->service->reject(
            $leaveRequest,
            null,
            $data['remarks'],
            Auth::guard('employee')->id(),
        );

        \App\Notifications\NotificationDispatcher::fire(
            'leave.rejected',
            $leaveRequest->loadMissing('employee', 'leaveType'),
            ['reason' => $data['remarks']],
        );

        return back()->with('success', 'Leave request rejected.');
    }

    /**
     * Ensure the request belongs to one of this manager's direct reports and
     * is still pending.
     */
    private function guardRequestInScope(LeaveRequest $leaveRequest): void
    {
        $me = $this->authorizeManager();

        $leaveRequest->loadMissing('employee');
        abort_unless($leaveRequest->employee && $leaveRequest->employee->reporting_manager_id === $me->id, 403);
        abort_unless($leaveRequest->status === 'pending', 422, 'This request has already been actioned.');
    }

    private function scopedCount(int $managerId, string $status): int
    {
        return LeaveRequest::whereHas('employee', fn ($q) => $q->where('reporting_manager_id', $managerId))
            ->where('status', $status)
            ->count();
    }
}
