<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRegularization;
use App\Services\AttendanceRegularizationService;
use App\Support\HrSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RegularizationController extends Controller
{
    public function __construct(private AttendanceRegularizationService $service)
    {
    }

    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('attendance_corrections.view'), 403);

        $requests = AttendanceRegularization::with(['employee', 'reviewer'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->boolean('breached'), fn ($q) => $q->where('escalated', true)->where('status', 'pending'))
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->employee_id))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $pendingCount = AttendanceRegularization::pending()->count();
        $breachedCount = AttendanceRegularization::pending()->where('escalated', true)->count();
        $tatHours = HrSettings::int('attendance_correction_tat_hours', 48);

        return view('admin.hr.regularizations.index', compact('requests', 'pendingCount', 'breachedCount', 'tatHours'));
    }

    public function show(AttendanceRegularization $regularization)
    {
        abort_unless(Auth::guard('admin')->user()->can('attendance_corrections.view'), 403);
        $regularization->load(['employee.department', 'attendance', 'reviewer']);

        return view('admin.hr.regularizations.show', compact('regularization'));
    }

    public function approve(Request $request, AttendanceRegularization $regularization)
    {
        abort_unless(Auth::guard('admin')->user()->can('attendance_corrections.manage'), 403);
        if ($regularization->status !== 'pending') {
            return back()->withErrors(['status' => 'This request has already been resolved.']);
        }
        $request->validate(['review_remarks' => ['nullable', 'string', 'max:500']]);
        $this->service->approve($regularization, $request->review_remarks);

        return back()->with('success', 'Request approved and attendance corrected.');
    }

    public function reject(Request $request, AttendanceRegularization $regularization)
    {
        abort_unless(Auth::guard('admin')->user()->can('attendance_corrections.manage'), 403);
        if ($regularization->status !== 'pending') {
            return back()->withErrors(['status' => 'This request has already been resolved.']);
        }
        $request->validate(['review_remarks' => ['required', 'string', 'max:500']]);
        $this->service->reject($regularization, $request->review_remarks);

        return back()->with('success', 'Request rejected.');
    }
}
