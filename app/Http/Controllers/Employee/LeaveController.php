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

        return view('employee.leaves.create', compact('types', 'balances'));
    }

    public function store(Request $request)
    {
        $employee = Auth::guard('employee')->user();

        $data = $request->validate([
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'from_date' => ['required', 'date', 'after_or_equal:today'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'day_portion' => ['required', 'in:full,first_half,second_half'],
            'reason' => ['required', 'string', 'min:5'],
        ]);
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
}
