<?php

namespace App\Http\Controllers\Admin\Asset;

use App\Http\Controllers\Controller;
use App\Models\AssetCategory;
use App\Models\AssetModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ModelController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('asset_models.view'), 403);

        $models = AssetModel::with('category')
            ->withCount('assets')
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('code', 'like', "%{$s}%")
                    ->orWhere('manufacturer', 'like', "%{$s}%")
                    ->orWhere('model_number', 'like', "%{$s}%");
            }))
            ->when($request->category_id, fn ($q, $id) => $q->where('category_id', $id))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $categories = AssetCategory::where('status', 'active')->orderBy('name')->get();

        return view('admin.assets.models.index', compact('models', 'categories'));
    }

    public function create()
    {
        abort_unless(Auth::guard('admin')->user()->can('asset_models.create'), 403);
        $categories = AssetCategory::where('status', 'active')->orderBy('name')->get();

        return view('admin.assets.models.create', compact('categories'));
    }

    public function store(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('asset_models.create'), 403);
        $data = $this->validateData($request);
        $data['specifications'] = $this->parseSpecs($request->input('specifications_raw'));
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('asset-models', 'public');
        }
        $data['created_by'] = Auth::guard('admin')->id();
        AssetModel::create($data);

        return redirect()->route('admin.assets.models.index')->with('success', 'Asset model created.');
    }

    public function show(AssetModel $model)
    {
        abort_unless(Auth::guard('admin')->user()->can('asset_models.view'), 403);
        $model->load('category');
        $assets = $model->assets()->with(['location', 'custodian'])->latest()->limit(50)->get();
        $stats = [
            'total_units' => $model->assets()->count(),
            'total_value' => (float) $model->assets()->sum('purchase_cost'),
            'book_value'  => (float) $model->assets()->sum('current_book_value'),
        ];

        return view('admin.assets.models.show', compact('model', 'assets', 'stats'));
    }

    public function edit(AssetModel $model)
    {
        abort_unless(Auth::guard('admin')->user()->can('asset_models.edit'), 403);
        $categories = AssetCategory::where('status', 'active')->orderBy('name')->get();

        return view('admin.assets.models.edit', compact('model', 'categories'));
    }

    public function update(Request $request, AssetModel $model)
    {
        abort_unless(Auth::guard('admin')->user()->can('asset_models.edit'), 403);
        $data = $this->validateData($request, $model->id);
        $data['specifications'] = $this->parseSpecs($request->input('specifications_raw'));
        if ($request->hasFile('image')) {
            if ($model->image) Storage::disk('public')->delete($model->image);
            $data['image'] = $request->file('image')->store('asset-models', 'public');
        }
        $data['updated_by'] = Auth::guard('admin')->id();
        $model->update($data);

        return redirect()->route('admin.assets.models.index')->with('success', 'Asset model updated.');
    }

    public function destroy(AssetModel $model)
    {
        abort_unless(Auth::guard('admin')->user()->can('asset_models.delete'), 403);

        if ($model->assets()->exists()) {
            return back()->with('error', 'Cannot delete — asset units exist for this model. Use "Discontinue" instead.');
        }
        if ($model->image) Storage::disk('public')->delete($model->image);
        $model->delete();

        return back()->with('success', 'Asset model deleted.');
    }

    public function discontinue(AssetModel $model)
    {
        abort_unless(Auth::guard('admin')->user()->can('asset_models.edit'), 403);
        $model->update([
            'status' => 'discontinued',
            'updated_by' => Auth::guard('admin')->id(),
        ]);

        return back()->with('success', 'Model marked discontinued. New assets cannot use it; existing units are unaffected.');
    }

    protected function validateData(Request $request, ?int $id = null): array
    {
        $unique = $id ? ',code,'.$id : ',code';

        return $request->validate([
            'code' => ['required', 'string', 'max:40', 'unique:asset_models'.$unique],
            'name' => ['required', 'string', 'max:150'],
            'category_id' => ['required', 'exists:asset_categories,id'],
            'manufacturer' => ['nullable', 'string', 'max:100'],
            'model_number' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:2048'],
            'default_depreciation_method' => ['required', 'in:straight_line,declining_balance,sum_of_years_digits,units_of_production'],
            'default_useful_life_years' => ['required', 'integer', 'min:1', 'max:60'],
            'default_salvage_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'manufacturer_warranty_months' => ['required', 'integer', 'min:0', 'max:240'],
            'status' => ['required', 'in:active,discontinued'],
        ]);
    }

    protected function parseSpecs(?string $raw): array
    {
        if (! $raw) return [];
        $out = [];
        foreach (preg_split('/\r\n|\r|\n/', $raw) as $line) {
            $line = trim($line);
            if ($line === '' || ! str_contains($line, ':')) continue;
            [$k, $v] = array_map('trim', explode(':', $line, 2));
            if ($k !== '') $out[$k] = $v;
        }

        return $out;
    }
}
