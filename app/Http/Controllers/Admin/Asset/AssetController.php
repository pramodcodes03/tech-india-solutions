<?php

namespace App\Http\Controllers\Admin\Asset;

use App\Exports\AssetRegisterExport;
use App\Http\Controllers\Controller;
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

        return view('admin.assets.assets.index', compact('assets', 'kpi', 'categories', 'models', 'locations', 'employees'));
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
            'status' => ['required', 'in:draft,in_storage,assigned,in_maintenance,retired,disposed'],
            'condition_rating' => ['required', 'in:excellent,good,fair,poor,damaged'],
            'notes' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);
    }
}
