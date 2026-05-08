<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\CompOffRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompOffController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('leaves.view'), 403);

        $compOffs = CompOffRequest::with('employee')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->orderByDesc('created_at')
            ->paginate(30)->withQueryString();

        $counts = [
            'pending'   => CompOffRequest::where('status', 'pending')->count(),
            'approved'  => CompOffRequest::where('status', 'approved')->count(),
            'rejected'  => CompOffRequest::where('status', 'rejected')->count(),
            'cancelled' => CompOffRequest::where('status', 'cancelled')->count(),
        ];

        return view('admin.hr.comp-off.index', compact('compOffs', 'counts'));
    }

    public function approve(Request $request, CompOffRequest $compOff)
    {
        abort_unless(Auth::guard('admin')->user()->can('leaves.approve'), 403);
        abort_unless($compOff->isPending(), 422);

        $compOff->update([
            'status'        => 'approved',
            'approved_by'   => Auth::guard('admin')->id(),
            'actioned_at'   => now(),
            'admin_remarks' => $request->input('admin_remarks'),
        ]);

        return back()->with('success', 'Comp-off approved. The day will be counted as paid.');
    }

    public function reject(Request $request, CompOffRequest $compOff)
    {
        abort_unless(Auth::guard('admin')->user()->can('leaves.approve'), 403);
        abort_unless($compOff->isPending(), 422);

        $compOff->update([
            'status'        => 'rejected',
            'approved_by'   => Auth::guard('admin')->id(),
            'actioned_at'   => now(),
            'admin_remarks' => $request->input('admin_remarks'),
        ]);

        return back()->with('success', 'Comp-off rejected.');
    }
}
