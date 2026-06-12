<?php

namespace App\Http\Controllers\Admin\Asset;

use App\Exports\AssetRegisterExport;
use App\Http\Controllers\Controller;
use App\Services\Asset\AssetImportService;
use App\Support\Tenancy\CurrentBusiness;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetLocation;
use App\Models\AssetModel;
use App\Models\Employee;
use App\Models\PurchaseOrder;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel as ExcelType;
use Maatwebsite\Excel\Facades\Excel;

class AssetController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('assets.view'), 403);

        $assets = Asset::with(['category', 'model', 'location', 'custodian'])
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('asset_code', 'like', "%{$s}%")
                    ->orWhere('serial_number', 'like', "%{$s}%");
            }))
            ->when($request->category_id, fn ($q, $id) => $q->where('category_id', $id))
            ->when($request->asset_model_id, fn ($q, $id) => $q->where('asset_model_id', $id))
            ->when($request->location_id, fn ($q, $id) => $q->where('location_id', $id))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->custodian_id, fn ($q, $id) => $q->where('current_custodian_id', $id))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $kpi = [
            'total'     => Asset::count(),
            'value'     => (float) Asset::sum('purchase_cost'),
            'book'      => (float) Asset::sum('current_book_value'),
            'assigned'  => Asset::where('status', 'assigned')->count(),
            'storage'   => Asset::where('status', 'in_storage')->count(),
            'maint'     => Asset::where('status', 'in_maintenance')->count(),
            'lost'      => Asset::where('is_lost', true)->count(),
            'warranty_soon' => Asset::whereNotNull('warranty_expiry_date')
                ->whereBetween('warranty_expiry_date', [now(), now()->addDays(60)])->count(),
            'eol_soon' => Asset::whereNotNull('end_of_life_date')
                ->whereBetween('end_of_life_date', [now(), now()->addDays(180)])->count(),
        ];

        $categories = AssetCategory::orderBy('name')->get();
        $models = AssetModel::orderBy('name')->get();
        $locations = AssetLocation::orderBy('name')->get();
        $employees = Employee::whereIn('status', ['active', 'probation'])->orderBy('first_name')->get();
        // Filter dropdown surfaces every configured status — including
        // disabled ones — so admins can still find legacy assets tagged
        // with a status they've since retired.
        $assetStatuses = \App\Models\AssetStatus::orderBy('sort_order')->orderBy('label')->get();

        return view('admin.assets.assets.index', compact('assets', 'kpi', 'categories', 'models', 'locations', 'employees', 'assetStatuses'));
    }

    public function export(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('assets.view'), 403);

        $assets = Asset::with(['category', 'model', 'location', 'custodian', 'vendor', 'purchaseOrder'])
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('asset_code', 'like', "%{$s}%")
                    ->orWhere('serial_number', 'like', "%{$s}%");
            }))
            ->when($request->category_id, fn ($q, $id) => $q->where('category_id', $id))
            ->when($request->asset_model_id, fn ($q, $id) => $q->where('asset_model_id', $id))
            ->when($request->location_id, fn ($q, $id) => $q->where('location_id', $id))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->custodian_id, fn ($q, $id) => $q->where('current_custodian_id', $id))
            ->latest()
            ->get();

        $format = strtolower($request->input('format', 'xlsx'));
        $stamp = now()->format('Y-m-d');

        if ($format === 'pdf') {
            $filters = $request->only(['search', 'category_id', 'location_id', 'status', 'custodian_id']);
            return Pdf::loadView('admin.assets.pdf.register', compact('assets', 'filters'))
                ->setPaper('a4', 'landscape')
                ->stream("asset-register-{$stamp}.pdf");
        }

        return Excel::download(new AssetRegisterExport($assets), "asset-register-{$stamp}.xlsx", ExcelType::XLSX);
    }

    /**
     * Bulk-import landing page.
     */
    public function importForm()
    {
        abort_unless(Auth::guard('admin')->user()->can('assets.create'), 403);

        $business = app(CurrentBusiness::class)->get();
        $categoryCount = $business ? AssetCategory::where('business_id', $business->id)->count() : 0;

        return view('admin.assets.assets.import', compact('categoryCount'));
    }

    /**
     * Process uploaded CSV / XLS / XLSX into assets.
     */
    public function import(Request $request, AssetImportService $service)
    {
        abort_unless(Auth::guard('admin')->user()->can('assets.create'), 403);

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xls,xlsx', 'max:10240'],
        ]);

        $business = app(CurrentBusiness::class)->get();
        abort_unless($business, 400, 'No active business.');

        $result = $service->import($request->file('file'), $business->id);

        $msg = "Imported {$result['imported']} assets";
        if ($result['failed'] > 0) {
            $msg .= ", skipped {$result['failed']} row(s) — see details below.";
        } else {
            $msg .= '.';
        }

        return redirect()->route('admin.assets.assets.index')
            ->with($result['failed'] > 0 ? 'warning' : 'success', $msg)
            ->with('import_errors', $result['errors']);
    }

    /**
     * Download an import template. ?format=xlsx (default) is a styled Excel
     * file with both a fully-filled and a minimum-fields-only sample row,
     * plus reference sheets that drive in-cell dropdowns for Category /
     * Location / Vendor / Custodian / Depreciation Method.
     * ?format=csv returns a plain CSV equivalent (no dropdowns possible).
     *
     * Pre-flight: if the current business has zero Asset Categories,
     * the download is blocked and the user is bounced back to the import
     * form with a link to create one — every imported row must reference
     * a Category, so a template with an empty Category dropdown is useless.
     */
    public function importTemplate(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('assets.create'), 403);

        $business = app(CurrentBusiness::class)->get();
        abort_unless($business, 400, 'No active business.');

        $businessId = $business->id;

        $categories = AssetCategory::where('business_id', $businessId)
            ->orderBy('name')->pluck('name')->all();

        if (empty($categories)) {
            return redirect()->route('admin.assets.assets.import-form')
                ->with('error', 'No Asset Categories exist yet. Add at least one before downloading the template — every imported row must reference a Category.');
        }

        $format = strtolower($request->input('format', 'xlsx'));

        if ($format === 'xlsx') {
            $locations = AssetLocation::where('business_id', $businessId)
                ->orderBy('name')->pluck('name')->all();
            $vendors = Vendor::where('business_id', $businessId)
                ->orderBy('name')->pluck('name')->all();
            $custodians = Employee::where('business_id', $businessId)
                ->whereNotIn('status', ['inactive', 'terminated', 'absconded'])
                ->orderBy('first_name')
                ->get(['first_name', 'last_name'])
                ->map(fn ($e) => trim(($e->first_name ?? '').' '.($e->last_name ?? '')))
                ->filter()
                ->values()
                ->all();

            return Excel::download(
                new \App\Exports\AssetImportTemplateExport($categories, $locations, $vendors, $custodians),
                'asset-import-template.xlsx',
                ExcelType::XLSX
            );
        }

        // CSV fallback
        $columns = [
            'Asset Code', 'Name', 'Serial Number', 'Category', 'Model', 'Manufacturer',
            'Location', 'Custodian', 'Vendor', 'PO Number',
            'Purchase Date', 'Purchase Cost', 'Salvage Value',
            'Warranty Expiry', 'Insurance Expiry', 'End of Life',
            'Depreciation Method', 'Useful Life (yrs)',
        ];

        $sample = [
            'AST-0001', 'Dell Latitude 5420', 'DL-12345', 'Electronics', 'Latitude 5420', 'Dell',
            'Head Office', '', '', '',
            '2026-01-15', '65000', '5000',
            '2027-01-15', '', '2031-01-15',
            'straight_line', '5',
        ];
        $minimal = [
            '', 'Wooden Desk', '', 'Furniture', '', '',
            '', '', '', '',
            '', '', '',
            '', '', '',
            '', '',
        ];

        $callback = function () use ($columns, $sample, $minimal) {
            $f = fopen('php://output', 'w');
            fputcsv($f, $columns);
            fputcsv($f, $sample);
            fputcsv($f, $minimal);
            fclose($f);
        };

        return response()->stream($callback, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="asset-import-template.csv"',
        ]);
    }

    public function create(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('assets.create'), 403);

        // Prefill from query string (e.g. coming from a PO show page)
        if ($request->filled('purchase_order_id')) {
            $request->session()->flashInput([
                'purchase_order_id' => $request->input('purchase_order_id'),
                'vendor_id' => $request->input('vendor_id'),
                'asset_model_id' => $request->input('asset_model_id'),
            ] + $request->session()->getOldInput());
        }

        return view('admin.assets.assets.create', $this->formData($request));
    }

    public function store(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('assets.create'), 403);
        $data = $this->validateData($request);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('assets', 'public');
        }

        // Auto code
        $data['asset_code'] = $data['asset_code'] ?: $this->generateCode($data['category_id']);

        // Initial book value
        $data['accumulated_depreciation'] = 0;
        $data['current_book_value'] = $data['purchase_cost'];

        $data['created_by'] = Auth::guard('admin')->id();
        unset($data['image']);

        $asset = Asset::create($data);

        return redirect()->route('admin.assets.assets.show', $asset)->with('success', 'Asset created.');
    }

    public function show(Asset $asset)
    {
        abort_unless(Auth::guard('admin')->user()->can('assets.view'), 403);
        $asset->load(['category', 'model', 'location', 'custodian.department', 'custodian.designation', 'vendor', 'purchaseOrder']);
        $assignments = $asset->assignments()->with(['employee', 'fromLocation', 'toLocation'])->limit(15)->get();
        $maintenance = $asset->maintenanceLogs()->with('technician')->limit(15)->get();
        $asset->load(['repairRequests.requester']);

        // Depreciation forecast (next 12 months, straight-line preview)
        $forecast = [];
        if ($asset->depreciation_method === 'straight_line' && $asset->useful_life_years > 0) {
            $monthly = max(0, ((float) $asset->purchase_cost - (float) $asset->salvage_value) / ($asset->useful_life_years * 12));
            $bv = (float) $asset->current_book_value;
            for ($i = 1; $i <= 12; $i++) {
                $bv = max((float) $asset->salvage_value, $bv - $monthly);
                $forecast[] = ['label' => Carbon::now()->addMonths($i)->format('M Y'), 'book_value' => round($bv, 2)];
            }
        }

        return view('admin.assets.assets.show', compact('asset', 'assignments', 'maintenance', 'forecast'));
    }

    public function edit(Asset $asset, Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('assets.edit'), 403);

        return view('admin.assets.assets.edit', array_merge($this->formData($request), compact('asset')));
    }

    public function update(Request $request, Asset $asset)
    {
        abort_unless(Auth::guard('admin')->user()->can('assets.edit'), 403);
        $data = $this->validateData($request, $asset->id);

        if ($request->hasFile('image')) {
            if ($asset->image_path) Storage::disk('public')->delete($asset->image_path);
            $data['image_path'] = $request->file('image')->store('assets', 'public');
        }
        unset($data['image']);

        // Recompute book value (purchase_cost may have changed)
        $data['current_book_value'] = max(0, (float) $data['purchase_cost'] - (float) $asset->accumulated_depreciation);

        $data['updated_by'] = Auth::guard('admin')->id();
        $asset->update($data);

        return redirect()->route('admin.assets.assets.show', $asset)->with('success', 'Asset updated.');
    }

    public function destroy(Asset $asset)
    {
        abort_unless(Auth::guard('admin')->user()->can('assets.delete'), 403);

        if ($asset->image_path) Storage::disk('public')->delete($asset->image_path);
        $asset->delete();

        return redirect()->route('admin.assets.assets.index')->with('success', 'Asset deleted.');
    }

    public function dispose(Request $request, Asset $asset)
    {
        abort_unless(Auth::guard('admin')->user()->can('assets.dispose'), 403);
        $request->validate([
            'disposal_date' => ['required', 'date'],
            'disposal_method' => ['required', 'in:scrap,sell,donate,write_off'],
            'realized_value' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $asset->update([
            'status' => 'disposed',
            'notes' => trim(($asset->notes ?? '')."\n\n[DISPOSED ".$request->disposal_date.'] method='.$request->disposal_method.', realized=₹'.($request->realized_value ?? 0).'. '.$request->notes),
            'updated_by' => Auth::guard('admin')->id(),
        ]);

        return redirect()->route('admin.assets.assets.show', $asset)->with('success', 'Asset disposed.');
    }

    public function markLost(Request $request, Asset $asset)
    {
        abort_unless(Auth::guard('admin')->user()->can('assets.audit'), 403);
        $asset->update([
            'is_lost' => ! $asset->is_lost,
            'updated_by' => Auth::guard('admin')->id(),
        ]);

        return back()->with('success', $asset->is_lost ? 'Asset marked as lost.' : 'Asset marked as found.');
    }

    public function toggleNonRepairable(Asset $asset)
    {
        abort_unless(Auth::guard('admin')->user()->can('assets.edit'), 403);
        $asset->update([
            'is_non_repairable' => ! $asset->is_non_repairable,
            'updated_by'        => Auth::guard('admin')->id(),
        ]);

        $label = $asset->is_non_repairable ? 'marked as Non-Repairable' : 'marked as Repairable';

        return back()->with('success', "Asset {$label}.");
    }

    protected function formData(Request $request): array
    {
        $modelId = $request->integer('asset_model_id');
        $autofill = null;
        if ($modelId) {
            $autofill = AssetModel::with('category')->find($modelId);
        }

        return [
            'categories' => AssetCategory::where('status', 'active')->orderBy('name')->get(),
            'models'     => AssetModel::with('category')->where('status', 'active')->orderBy('name')->get(),
            'locations'  => AssetLocation::where('status', 'active')->orderBy('name')->get(),
            'employees'  => Employee::whereIn('status', ['active', 'probation'])->orderBy('first_name')->get(),
            'vendors'    => Vendor::where('status', 'active')->orderBy('name')->get(),
            'purchaseOrders' => PurchaseOrder::with('vendor')->latest('po_date')->limit(200)->get(),
            'autofill'   => $autofill,
            // Status dropdown is admin-configurable — see asset_statuses
            // table. Only active rows are surfaced so disabling a status
            // hides it from create/edit without losing historical assets
            // that still reference its slug.
            'assetStatuses' => \App\Models\AssetStatus::where('is_active', true)->orderBy('sort_order')->orderBy('label')->get(),
        ];
    }

    protected function generateCode(int $categoryId): string
    {
        $cat = AssetCategory::find($categoryId);
        $prefix = $cat ? strtoupper(substr($cat->code, 0, 3)) : 'ASS';
        $next = (Asset::where('category_id', $categoryId)->withTrashed()->count() + 1);

        return $prefix.'-'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    protected function validateData(Request $request, ?int $id = null): array
    {
        $unique = $id ? ',asset_code,'.$id : ',asset_code';

        return $request->validate([
            'asset_code' => ['nullable', 'string', 'max:40', 'unique:assets'.$unique],
            'name' => ['required', 'string', 'max:150'],
            'serial_number' => ['nullable', 'string', 'max:120'],
            'category_id' => ['required', 'exists:asset_categories,id'],
            'asset_model_id' => ['nullable', 'exists:asset_models,id'],
            'location_id' => ['nullable', 'exists:asset_locations,id'],
            'current_custodian_id' => ['nullable', 'exists:employees,id'],
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'purchase_order_id' => ['nullable', 'exists:purchase_orders,id'],
            'purchase_date' => ['nullable', 'date'],
            'purchase_cost' => ['required', 'numeric', 'min:0'],
            'salvage_value' => ['required', 'numeric', 'min:0'],
            'warranty_expiry_date' => ['nullable', 'date'],
            'insurance_expiry_date' => ['nullable', 'date'],
            'end_of_life_date' => ['nullable', 'date'],
            'depreciation_method' => ['required', 'in:straight_line,declining_balance,sum_of_years_digits,units_of_production,none'],
            'useful_life_years' => ['required', 'integer', 'min:0', 'max:60'],
            'depreciation_start_date' => ['nullable', 'date'],
            // Validate against the active configured statuses for this
            // business. Inactive / soft-deleted statuses are rejected so
            // disabling an option in the admin panel actually blocks new
            // assets from being saved with it.
            'status' => ['required', 'string', 'exists:asset_statuses,key'],
            'condition_rating' => ['required', 'in:excellent,good,fair,poor,damaged'],
            'notes' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);
    }
}
