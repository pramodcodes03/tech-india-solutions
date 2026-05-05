<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\LeaveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveController extends Controller
{
    public function __construct(protected LeaveService $service) {}

    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('leaves.view'), 403);

        $requests = LeaveRequest::with(['employee.department', 'leaveType', 'approver'])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->leave_type_id, fn ($q, $id) => $q->where('leave_type_id', $id))
            ->when($request->search, fn ($q, $s) => $q->whereHas('employee', fn ($e) => $e->where(function ($q) use ($s) {
                $q->where('first_name', 'like', "%{$s}%")
                    ->orWhere('last_name', 'like', "%{$s}%")
                    ->orWhere('employee_code', 'like', "%{$s}%");
            })))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $leaveTypes = LeaveType::where('status', 'active')->orderBy('name')->get();

        $counts = [
            'pending' => LeaveRequest::where('status', 'pending')->count(),
            'approved' => LeaveRequest::where('status', 'approved')->count(),
            'rejected' => LeaveRequest::where('status', 'rejected')->count(),
        ];

        return view('admin.hr.leaves.index', compact('requests', 'leaveTypes', 'counts'));
    }

    public function show(LeaveRequest $leaveRequest)
    {
        abort_unless(Auth::guard('admin')->user()->can('leaves.view'), 403);
        $leaveRequest->load(['employee.department', 'employee.designation', 'leaveType', 'approver']);

        return view('admin.hr.leaves.show', ['request' => $leaveRequest]);
    }

    public function approve(Request $request, LeaveRequest $leaveRequest)
    {
        abort_unless(Auth::guard('admin')->user()->can('leaves.approve'), 403);
        $data = $request->validate([
            'remarks' => ['nullable', 'string'],
            'paid_days' => ['nullable', 'numeric', 'min:0', 'max:'.$leaveRequest->days],
        ]);
        $paid = array_key_exists('paid_days', $data) && $data['paid_days'] !== null && $data['paid_days'] !== ''
            ? (float) $data['paid_days']
            : null;

        $this->service->approve(
            $leaveRequest,
            Auth::guard('admin')->id(),
            $data['remarks'] ?? null,
            $paid
        );

        \App\Notifications\NotificationDispatcher::fire(
            'leave.approved',
            $leaveRequest->loadMissing('employee', 'leaveType'),
            ['remarks' => $data['remarks'] ?? null],
        );

        $msg = 'Leave request approved.';
        if ($paid !== null && $paid < (float) $leaveRequest->days) {
            $unpaid = (float) $leaveRequest->days - $paid;
            $msg = sprintf('Approved: %.1f paid + %.1f unpaid (LOP).', $paid, $unpaid);
        }

        return back()->with('success', $msg);
    }

    public function reject(Request $request, LeaveRequest $leaveRequest)
    {
        abort_unless(Auth::guard('admin')->user()->can('leaves.reject'), 403);
        $data = $request->validate(['remarks' => ['required', 'string', 'min:3']]);
        $this->service->reject($leaveRequest, Auth::guard('admin')->id(), $data['remarks']);

        \App\Notifications\NotificationDispatcher::fire(
            'leave.rejected',
            $leaveRequest->loadMissing('employee', 'leaveType'),
            ['reason' => $data['remarks']],
        );

        return back()->with('success', 'Leave request rejected.');
    }
}
