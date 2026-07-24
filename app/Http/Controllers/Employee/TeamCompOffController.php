<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\CompOffRequest;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Reporting-Manager comp-off approvals from the employee portal — the comp-off
 * counterpart of TeamCompOffController. A "manager" is an Employee that others
 * report to (employees.reporting_manager_id); they see and action comp-off
 * requests for their direct reports only.
 */
class TeamCompOffController extends Controller
{
    private function isManager(int $employeeId): bool
    {
        return Employee::where('reporting_manager_id', $employeeId)->exists();
    }

    private function authorizeManager(): Employee
    {
        $me = Auth::guard('employee')->user();
        abort_unless($this->isManager($me->id), 403, 'You have no direct reports.');

        return $me;
    }

    public function index(Request $request)
    {
        $me = $this->authorizeManager();

        $requests = CompOffRequest::with(['employee.department', 'approver', 'approverEmployee'])
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

        return view('employee.team-comp-off.index', compact('requests', 'counts'));
    }

    public function approve(Request $request, CompOffRequest $compOff)
    {
        $this->guardRequestInScope($compOff);

        $data = $request->validate(['remarks' => ['nullable', 'string', 'max:500']]);

        $compOff->update([
            'status'                  => 'approved',
            'approved_by_employee_id' => Auth::guard('employee')->id(),
            'actioned_at'             => now(),
            'admin_remarks'           => $data['remarks'] ?? null,
        ]);

        \App\Models\AdminNotification::markRelatedAsRead($compOff, ['comp_off.requested']);
        \App\Notifications\NotificationDispatcher::fire('comp_off.approved', $compOff->loadMissing('employee'));

        return back()->with('success', 'Comp-off approved. The day will be counted as paid.');
    }

    public function reject(Request $request, CompOffRequest $compOff)
    {
        $this->guardRequestInScope($compOff);

        $data = $request->validate(['remarks' => ['required', 'string', 'min:3', 'max:500']]);

        $compOff->update([
            'status'                  => 'rejected',
            'approved_by_employee_id' => Auth::guard('employee')->id(),
            'actioned_at'             => now(),
            'admin_remarks'           => $data['remarks'],
        ]);

        \App\Models\AdminNotification::markRelatedAsRead($compOff, ['comp_off.requested']);
        \App\Notifications\NotificationDispatcher::fire('comp_off.rejected', $compOff->loadMissing('employee'), ['reason' => $data['remarks']]);

        return back()->with('success', 'Comp-off request rejected.');
    }

    private function guardRequestInScope(CompOffRequest $compOff): void
    {
        $me = $this->authorizeManager();

        $compOff->loadMissing('employee');
        abort_unless($compOff->employee && $compOff->employee->reporting_manager_id === $me->id, 403);
        abort_unless($compOff->status === 'pending', 422, 'This request has already been actioned.');
    }

    private function scopedCount(int $managerId, string $status): int
    {
        return CompOffRequest::whereHas('employee', fn ($q) => $q->where('reporting_manager_id', $managerId))
            ->where('status', $status)
            ->count();
    }
}
