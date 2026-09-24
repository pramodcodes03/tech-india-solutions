<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Exports\LeaveRequestsExport;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Notifications\NotificationDispatcher;
use App\Services\LeaveRequestReportService;
use App\Services\LeaveService;
use App\Support\Tenancy\CurrentBusiness;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class LeaveController extends Controller
{
    public function __construct(
        protected LeaveService $service,
        protected LeaveRequestReportService $report,
    ) {}

    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('leaves.view'), 403);

        // One filter definition, shared with both exports — see
        // LeaveRequestReportService.
        $requests = $this->report->query($this->filters($request))
            ->paginate(20)
            ->withQueryString();

        $leaveTypes = LeaveType::where('status', 'active')->orderBy('name')->get();
        $departments = Department::orderBy('name')->get();

        $counts = [
            'pending' => LeaveRequest::where('status', 'pending')->count(),
            'approved' => LeaveRequest::where('status', 'approved')->count(),
            'rejected' => LeaveRequest::where('status', 'rejected')->count(),
        ];

        return view('admin.hr.leaves.index', compact('requests', 'leaveTypes', 'departments', 'counts'));
    }

    /**
     * The filters the screen and both exports understand.
     *
     * Read once, here, so an export can never be built from a different set
     * than the one the person was looking at.
     */
    private function filters(Request $request): array
    {
        return $request->only([
            'status', 'leave_type_id', 'department_id', 'search', 'year', 'month', 'date',
        ]);
    }

    /** The current filtered list as a spreadsheet. */
    public function exportExcel(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('leaves.view'), 403);

        return Excel::download(
            new LeaveRequestsExport($this->report, $this->filters($request)),
            'leave-requests-'.now()->format('Y-m-d').'.xlsx',
        );
    }

    /** The same list as a PDF, through the shared letterhead report template. */
    public function exportPdf(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('leaves.view'), 403);

        $filters = $this->filters($request);
        $rows = $this->report->rows($filters);

        $pdf = Pdf::loadView('pdf.documents.report-table', [
            'headings' => LeaveRequestReportService::HEADINGS,
            'rows' => $rows,
            'summary' => ['Requests' => count($rows)],
            'period' => $this->report->periodLabel($filters),
            'business' => app(CurrentBusiness::class)->get(),
            'watermark' => null,
            'generatedAt' => now()->format('d M Y, h:i A'),
            'documentName' => 'Leave Requests Report',
            'documentKey' => 'leave_requests_export',
            'landscape' => true,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('leave-requests-'.now()->format('Y-m-d').'.pdf');
    }

    public function show(LeaveRequest $leaveRequest)
    {
        abort_unless(Auth::guard('admin')->user()->can('leaves.view'), 403);
        $leaveRequest->load(['employee.department', 'employee.designation', 'leaveType', 'approver', 'approverEmployee', 'splits.leaveType']);

        $balance = LeaveBalance::where('employee_id', $leaveRequest->employee_id)
            ->where('leave_type_id', $leaveRequest->leave_type_id)
            ->where('year', $leaveRequest->from_date->year)
            ->first();

        return view('admin.hr.leaves.show', [
            'request' => $leaveRequest,
            'balance' => $balance,
            // Excludes this request's own pending hold — see LeaveService.
            'available' => $this->service->availableForRequest($leaveRequest),
            // Why the day count is what it is. A four-day range that consumed
            // three days is usually a week-off or holiday in the middle, and
            // without this the reviewer has no way to tell.
            'dayBreakdown' => $this->service->dayBreakdown($leaveRequest),
        ]);
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

        try {
            $this->service->approve(
                $leaveRequest,
                Auth::guard('admin')->id(),
                $data['remarks'] ?? null,
                $paid
            );
        } catch (\RuntimeException $e) {
            // Approving a second request over the same dates would deduct the
            // days twice — say so rather than 500.
            return back()->with('error', $e->getMessage());
        }

        NotificationDispatcher::fire(
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

        NotificationDispatcher::fire(
            'leave.rejected',
            $leaveRequest->loadMissing('employee', 'leaveType'),
            ['reason' => $data['remarks']],
        );

        return back()->with('success', 'Leave request rejected.');
    }
}
