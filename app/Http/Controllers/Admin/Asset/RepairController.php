<?php

namespace App\Http\Controllers\Admin\Asset;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetRepairActivityLog;
use App\Models\AssetRepairRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RepairController extends Controller
{
    // ── Index ────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('assets.view'), 403);

        $requests = AssetRepairRequest::with(['asset', 'requester'])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->asset_type, fn ($q, $t) => $q->where('asset_type', $t))
            ->when($request->vendor, fn ($q, $v) => $q->where('vendor_name', 'like', "%{$v}%"))
            ->when($request->date_from, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->date_to, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        $counts = [
            'pending'               => AssetRepairRequest::where('status', 'pending')->count(),
            'approved'              => AssetRepairRequest::where('status', 'approved')->count(),
            'rejected'              => AssetRepairRequest::where('status', 'rejected')->count(),
            'cost_approval_pending' => AssetRepairRequest::where('status', 'cost_approval_pending')->count(),
            'cost_approved'         => AssetRepairRequest::where('status', 'cost_approved')->count(),
        ];

        $assetTypes = AssetRepairRequest::select('asset_type')
            ->distinct()
            ->whereNotNull('asset_type')
            ->pluck('asset_type');

        return view('admin.assets.repair.index', compact('requests', 'counts', 'assetTypes'));
    }

    // ── Create ───────────────────────────────────────────────────────────

    public function create(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('assets.create'), 403);

        // Only repairable, non-disposed, non-retired assets
        $assets = Asset::where('is_non_repairable', false)
            ->whereNotIn('status', ['disposed', 'retired'])
            ->orderBy('name')
            ->get(['id', 'asset_code', 'name', 'status']);

        $categories = AssetCategory::where('status', 'active')->orderBy('name')->get(['id', 'name']);

        $preselectedAsset = null;
        if ($request->asset_id) {
            $preselectedAsset = Asset::where('is_non_repairable', false)
                ->find($request->asset_id);
        }

        return view('admin.assets.repair.create', compact('assets', 'categories', 'preselectedAsset'));
    }

    // ── Store ────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('assets.create'), 403);

        $data = $request->validate([
            'asset_id'             => ['required', 'integer'],
            'asset_type'           => ['nullable', 'string', 'max:100'],
            'vendor_name'          => ['required', 'string', 'max:200'],
            'repair_delivery_date' => ['required', 'date', 'after_or_equal:today'],
            'description'          => ['required', 'string', 'max:2000'],
            'estimated_cost'       => ['nullable', 'numeric', 'min:0', 'max:99999999'],
        ]);

        // Verify asset belongs to current business and is repairable
        $asset = Asset::where('is_non_repairable', false)
            ->whereNotIn('status', ['disposed', 'retired'])
            ->findOrFail($data['asset_id']);

        $admin = Auth::guard('admin')->user();

        DB::transaction(function () use ($data, $asset, $admin) {
            $code = $this->generateRequestCode();

            $repairRequest = AssetRepairRequest::create([
                'business_id'          => $asset->business_id,
                'request_code'         => $code,
                'asset_id'             => $asset->id,
                'asset_type'           => $data['asset_type'] ?? $asset->category?->name,
                'vendor_name'          => $data['vendor_name'],
                'repair_delivery_date' => $data['repair_delivery_date'],
                'description'          => $data['description'],
                'estimated_cost'       => $data['estimated_cost'] ?? null,
                'status'               => 'pending',
                'requested_by'         => $admin->id,
            ]);

            $this->logActivity($repairRequest, $admin, 'request_raised', null, 'pending', null);
        });

        return redirect()->route('admin.assets.repair.index')
            ->with('success', 'Repair approval request submitted successfully.');
    }

    // ── Show ─────────────────────────────────────────────────────────────

    public function show(AssetRepairRequest $repair)
    {
        abort_unless(Auth::guard('admin')->user()->can('assets.view'), 403);

        $repair->load(['asset.category', 'requester', 'approver', 'costingApprover', 'activityLogs']);

        return view('admin.assets.repair.show', compact('repair'));
    }

    // ── Approve ──────────────────────────────────────────────────────────

    public function approve(Request $request, AssetRepairRequest $repair)
    {
        abort_unless(Auth::guard('admin')->user()->can('assets.edit'), 403);
        abort_unless($repair->isPending(), 422);

        $data = $request->validate([
            'approval_remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $admin = Auth::guard('admin')->user();

        $repair->update([
            'status'           => 'approved',
            'approved_by'      => $admin->id,
            'approved_at'      => now(),
            'approval_remarks' => $data['approval_remarks'] ?? null,
        ]);

        $this->logActivity($repair, $admin, 'approved', $data['approval_remarks'] ?? null, 'approved', null);

        return back()->with('success', 'Repair request approved.');
    }

    // ── Reject ───────────────────────────────────────────────────────────

    public function reject(Request $request, AssetRepairRequest $repair)
    {
        abort_unless(Auth::guard('admin')->user()->can('assets.edit'), 403);
        abort_unless($repair->isPending(), 422);

        $data = $request->validate([
            'approval_remarks' => ['required', 'string', 'max:1000'],
        ]);

        $admin = Auth::guard('admin')->user();

        $repair->update([
            'status'           => 'rejected',
            'approved_by'      => $admin->id,
            'approved_at'      => now(),
            'approval_remarks' => $data['approval_remarks'],
        ]);

        $this->logActivity($repair, $admin, 'rejected', $data['approval_remarks'], 'rejected', null);

        return back()->with('success', 'Repair request rejected.');
    }

    // ── Raise Costing Approval ───────────────────────────────────────────

    public function requestCosting(Request $request, AssetRepairRequest $repair)
    {
        abort_unless(Auth::guard('admin')->user()->can('assets.create'), 403);
        abort_unless($repair->canRaiseCostApproval(), 422);

        $data = $request->validate([
            'costing_requested_amount' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'costing_description'      => ['required', 'string', 'max:2000'],
        ]);

        $admin = Auth::guard('admin')->user();

        $repair->update([
            'status'                    => 'cost_approval_pending',
            'costing_requested_amount'  => $data['costing_requested_amount'],
            'costing_description'       => $data['costing_description'],
            'costing_status'            => 'pending',
        ]);

        $this->logActivity(
            $repair, $admin, 'cost_approval_raised',
            "Amount: ₹" . number_format($data['costing_requested_amount'], 2),
            'cost_approval_pending', 'pending'
        );

        return back()->with('success', 'Costing approval request submitted to admin.');
    }

    // ── Approve Costing ──────────────────────────────────────────────────

    public function approveCosting(Request $request, AssetRepairRequest $repair)
    {
        abort_unless(Auth::guard('admin')->user()->can('assets.edit'), 403);
        abort_unless($repair->isCostPending(), 422);

        $data = $request->validate([
            'costing_remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $admin = Auth::guard('admin')->user();

        $repair->update([
            'status'              => 'cost_approved',
            'costing_status'      => 'approved',
            'costing_approved_by' => $admin->id,
            'costing_approved_at' => now(),
            'costing_remarks'     => $data['costing_remarks'] ?? null,
        ]);

        $this->logActivity($repair, $admin, 'cost_approved', $data['costing_remarks'] ?? null, 'cost_approved', 'approved');

        return back()->with('success', 'Repair costing approved.');
    }

    // ── Reject Costing ───────────────────────────────────────────────────

    public function rejectCosting(Request $request, AssetRepairRequest $repair)
    {
        abort_unless(Auth::guard('admin')->user()->can('assets.edit'), 403);
        abort_unless($repair->isCostPending(), 422);

        $data = $request->validate([
            'costing_remarks' => ['required', 'string', 'max:1000'],
        ]);

        $admin = Auth::guard('admin')->user();

        $repair->update([
            'status'              => 'cost_rejected',
            'costing_status'      => 'rejected',
            'costing_approved_by' => $admin->id,
            'costing_approved_at' => now(),
            'costing_remarks'     => $data['costing_remarks'],
        ]);

        $this->logActivity($repair, $admin, 'cost_rejected', $data['costing_remarks'], 'cost_rejected', 'rejected');

        return back()->with('success', 'Repair costing rejected.');
    }

    // ── Private helpers ──────────────────────────────────────────────────

    private function generateRequestCode(): string
    {
        $prefix = 'ARR';
        $last = AssetRepairRequest::withoutGlobalScopes()
            ->where('request_code', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->value('request_code');

        $next = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;

        return $prefix . str_pad($next, 5, '0', STR_PAD_LEFT);
    }

    private function logActivity(
        AssetRepairRequest $repair,
        $admin,
        string $event,
        ?string $remarks,
        string $statusSnapshot,
        ?string $costingStatusSnapshot
    ): void {
        AssetRepairActivityLog::create([
            'business_id'              => $repair->business_id,
            'asset_repair_request_id'  => $repair->id,
            'performed_by'             => $admin->id,
            'performed_by_name'        => $admin->name,
            'event'                    => $event,
            'remarks'                  => $remarks,
            'status_snapshot'          => $statusSnapshot,
            'costing_status_snapshot'  => $costingStatusSnapshot,
            'performed_at'             => now(),
        ]);
    }
}
