<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\CompOffRequest;
use App\Notifications\NotificationDispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompOffController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('leaves.view'), 403);

        // Include soft-deleted employees in the eager load so a comp-off
        // submitted by someone who has since been terminated/soft-deleted
        // still shows the original name instead of returning null and
        // crashing the view.
        $compOffs = CompOffRequest::with(['employee' => fn ($q) => $q->withTrashed()])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->orderByDesc('created_at')
            ->paginate(30)->withQueryString();

        $counts = [
            'pending' => CompOffRequest::where('status', 'pending')->count(),
            'approved' => CompOffRequest::where('status', 'approved')->count(),
            'rejected' => CompOffRequest::where('status', 'rejected')->count(),
            'cancelled' => CompOffRequest::where('status', 'cancelled')->count(),
        ];

        return view('admin.hr.comp-off.index', compact('compOffs', 'counts'));
    }

    public function approve(Request $request, CompOffRequest $compOff)
    {
        abort_unless(Auth::guard('admin')->user()->can('leaves.approve'), 403);
        abort_unless($compOff->isPending(), 422);

        $compOff->update([
            'status' => 'approved',
            'approved_by' => Auth::guard('admin')->id(),
            'actioned_at' => now(),
            'admin_remarks' => $request->input('admin_remarks'),
        ]);

        AdminNotification::markRelatedAsRead($compOff, ['comp_off.requested']);
        NotificationDispatcher::fire(
            'comp_off.approved',
            $compOff->loadMissing('employee'),
        );

        return back()->with('success', 'Comp-off approved. The day will be counted as paid.');
    }

    public function reject(Request $request, CompOffRequest $compOff)
    {
        abort_unless(Auth::guard('admin')->user()->can('leaves.approve'), 403);
        abort_unless($compOff->isPending(), 422);

        $compOff->update([
            'status' => 'rejected',
            'approved_by' => Auth::guard('admin')->id(),
            'actioned_at' => now(),
            'admin_remarks' => $request->input('admin_remarks'),
        ]);

        AdminNotification::markRelatedAsRead($compOff, ['comp_off.requested']);
        NotificationDispatcher::fire(
            'comp_off.rejected',
            $compOff->loadMissing('employee'),
        );

        return back()->with('success', 'Comp-off rejected.');
    }

    /**
     * Remove a comp-off request outright.
     *
     * Approved comp-offs are read live by AttendanceService — the comp date
     * shows as a paid `comp_off` day and counts toward paid days. Deleting an
     * approved row therefore takes that day back; already-generated payslips
     * are snapshots and stay as they are. The UI spells this out before asking
     * for confirmation.
     */
    public function destroy(CompOffRequest $compOff)
    {
        abort_unless(Auth::guard('admin')->user()->can('leaves.delete'), 403);

        $wasApproved = $compOff->status === 'approved';

        AdminNotification::markRelatedAsRead($compOff, ['comp_off.requested']);
        $compOff->delete();

        return back()->with('success', $wasApproved
            ? 'Comp-off request deleted. The comp day no longer counts as paid.'
            : 'Comp-off request deleted.');
    }
}
