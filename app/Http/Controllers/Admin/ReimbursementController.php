<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReimbursementClaim;
use App\Services\ReimbursementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ReimbursementController extends Controller
{
    public function __construct(private ReimbursementService $service)
    {
    }

    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('reimbursements.view'), 403);

        $claims = ReimbursementClaim::with(['employee', 'category', 'reviewer'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->employee_id))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $counts = ReimbursementClaim::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

        return view('admin.reimbursements.index', compact('claims', 'counts'));
    }

    public function show(ReimbursementClaim $reimbursement)
    {
        abort_unless(Auth::guard('admin')->user()->can('reimbursements.view'), 403);
        $reimbursement->load('employee.department', 'category', 'reviewer', 'logs.admin', 'logs.employee');

        return view('admin.reimbursements.show', ['claim' => $reimbursement]);
    }

    public function review(Request $request, ReimbursementClaim $reimbursement)
    {
        abort_unless(Auth::guard('admin')->user()->can('reimbursements.review'), 403);
        $data = $request->validate([
            'status' => ['required', 'in:under_review,approved,rejected,disbursed'],
            'remarks' => ['nullable', 'string', 'max:500'],
            'approved_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_reference' => ['nullable', 'string', 'max:120'],
        ]);

        if ($data['status'] === 'rejected' && empty($data['remarks'])) {
            return back()->withErrors(['remarks' => 'A reason is required to reject a claim.']);
        }

        $this->service->transition($reimbursement, $data['status'], [
            'remarks' => $data['remarks'] ?? null,
            'approved_amount' => $data['approved_amount'] ?? null,
            'payment_reference' => $data['payment_reference'] ?? null,
        ]);

        return back()->with('success', 'Claim updated.');
    }

    public function bill(ReimbursementClaim $reimbursement)
    {
        abort_unless(Auth::guard('admin')->user()->can('reimbursements.view'), 403);
        abort_unless($reimbursement->bill_path && Storage::disk('public')->exists($reimbursement->bill_path), 404);

        return Storage::disk('public')->download($reimbursement->bill_path, $reimbursement->claim_code);
    }
}
