<?php

namespace App\Http\Controllers\Admin\Asset;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AssetCategory;
use App\Models\AssetLocation;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Bulk operations over many assets in a single action — assign, change
 * category / status / condition / location, multi-field edit, location
 * transfer and delete. Every action is audit-logged per asset.
 */
class BulkController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('assets.edit'), 403);

        $assets = Asset::with(['category', 'location', 'custodian'])
            ->when($request->search, fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$s}%")
                ->orWhere('asset_code', 'like', "%{$s}%")
                ->orWhere('serial_number', 'like', "%{$s}%")))
            ->when($request->category_id, fn ($q, $id) => $q->where('category_id', $id))
            ->when($request->location_id, fn ($q, $id) => $q->where('location_id', $id))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->orderBy('asset_code')
            ->paginate(50)
            ->withQueryString();

        $categories = AssetCategory::orderBy('name')->get();
        $locations = AssetLocation::orderBy('name')->get();
        $employees = Employee::whereIn('status', ['active', 'probation'])->orderBy('first_name')->get();
        $assetStatuses = \App\Models\AssetStatus::where('is_active', true)->orderBy('sort_order')->orderBy('label')->get();

        return view('admin.assets.bulk.index', compact('assets', 'categories', 'locations', 'employees', 'assetStatuses'));
    }

    public function apply(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        abort_unless($admin->can('assets.edit'), 403);

        $data = $request->validate([
            'asset_ids' => ['required', 'array', 'min:1'],
            'asset_ids.*' => ['integer'],
            'action' => ['required', 'in:assign,change_category,change_status,change_condition,change_location,transfer_location,edit,delete'],
            // Optional payloads depending on action.
            'employee_id' => ['nullable', 'exists:employees,id'],
            'category_id' => ['nullable', 'exists:asset_categories,id'],
            'location_id' => ['nullable', 'exists:asset_locations,id'],
            'status' => ['nullable', 'string', 'exists:asset_statuses,key'],
            'condition_rating' => ['nullable', 'in:excellent,good,fair,poor,damaged'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $assets = Asset::whereIn('id', $data['asset_ids'])->get();
        if ($assets->isEmpty()) {
            return back()->withErrors(['asset_ids' => 'No matching assets found.']);
        }

        // Permission gates for the destructive / restricted actions.
        if ($data['action'] === 'delete') {
            abort_unless($admin->can('assets.delete'), 403);
        }
        if (in_array($data['action'], ['assign', 'transfer_location'])) {
            abort_unless($admin->can('assets.assign'), 403);
        }

        $count = 0;
        DB::transaction(function () use ($assets, $data, &$count, $admin) {
            foreach ($assets as $asset) {
                $changes = [];

                switch ($data['action']) {
                    case 'assign':
                        if (empty($data['employee_id'])) {
                            abort(422, 'Select an employee to assign to.');
                        }
                        AssetAssignment::create([
                            'business_id' => $asset->business_id,
                            'assignment_code' => 'BAS-'.$asset->id.'-'.now()->format('ymdHis'),
                            'asset_id' => $asset->id,
                            'employee_id' => $data['employee_id'],
                            'from_location_id' => $asset->location_id,
                            'to_location_id' => $asset->location_id,
                            'assigned_at' => now(),
                            'action_type' => 'assign',
                            'condition_at_assign' => $asset->condition_rating,
                            'notes' => $data['notes'] ?? 'Bulk assignment',
                            'issued_by' => $admin->id,
                        ]);
                        $changes = ['current_custodian_id' => $data['employee_id'], 'status' => 'assigned'];
                        break;

                    case 'change_category':
                        $changes = ['category_id' => $data['category_id']];
                        break;

                    case 'change_status':
                        $changes = ['status' => $data['status']];
                        break;

                    case 'change_condition':
                        $changes = ['condition_rating' => $data['condition_rating']];
                        break;

                    case 'change_location':
                    case 'transfer_location':
                        $from = $asset->location_id;
                        $changes = ['location_id' => $data['location_id']];
                        if ($data['action'] === 'transfer_location' && $from != $data['location_id']) {
                            AssetAssignment::create([
                                'business_id' => $asset->business_id,
                                'assignment_code' => 'BTR-'.$asset->id.'-'.now()->format('ymdHis'),
                                'asset_id' => $asset->id,
                                'employee_id' => $asset->current_custodian_id,
                                'from_location_id' => $from,
                                'to_location_id' => $data['location_id'],
                                'assigned_at' => now(),
                                'action_type' => 'transfer',
                                'notes' => $data['notes'] ?? 'Bulk location transfer',
                                'issued_by' => $admin->id,
                            ]);
                        }
                        break;

                    case 'edit':
                        // Multi-field edit: only apply the fields that were provided.
                        foreach (['category_id', 'location_id', 'status', 'condition_rating'] as $f) {
                            if (! empty($data[$f])) {
                                $changes[$f] = $data[$f];
                            }
                        }
                        break;

                    case 'delete':
                        activity()->performedOn($asset)->causedBy($admin)
                            ->withProperties(['bulk' => true])->log('Asset bulk-deleted');
                        $asset->delete();
                        $count++;
                        continue 2;
                }

                if ($changes) {
                    $changes['updated_by'] = $admin->id;
                    $asset->update($changes);
                    activity()->performedOn($asset)->causedBy($admin)
                        ->withProperties(['bulk' => true, 'action' => $data['action'], 'changes' => $changes])
                        ->log('Asset bulk-updated');
                    $count++;
                }
            }
        });

        // Return to wherever the action was triggered from (the Asset Register
        // when driven inline, or the standalone bulk page) so the user stays in
        // context. Falls back to the bulk page if no referrer is present.
        return redirect()->to(url()->previous() ?: route('admin.assets.bulk.index'))
            ->with('success', "Bulk action applied to {$count} asset(s).");
    }
}
