<?php

namespace App\Http\Controllers\Admin\Asset;

use App\Http\Controllers\Controller;
use App\Models\AssetCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('asset_categories.view'), 403);

        $categories = AssetCategory::withCount(['assets', 'models'])
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%");
            }))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.assets.categories.index', compact('categories'));
    }

    public function create()
    {
        abort_unless(Auth::guard('admin')->user()->can('asset_categories.create'), 403);

        return view('admin.assets.categories.create');
    }

    public function store(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('asset_categories.create'), 403);
        $data = $this->validateData($request);
        $data['created_by'] = Auth::guard('admin')->id();
        AssetCategory::create($data);

        return redirect()->route('admin.assets.categories.index')->with('success', 'Asset category created.');
    }

    public function edit(AssetCategory $category)
    {
        abort_unless(Auth::guard('admin')->user()->can('asset_categories.edit'), 403);

        return view('admin.assets.categories.edit', compact('category'));
    }

    public function update(Request $request, AssetCategory $category)
    {
        abort_unless(Auth::guard('admin')->user()->can('asset_categories.edit'), 403);
        $data = $this->validateData($request, $category->id);
        $data['updated_by'] = Auth::guard('admin')->id();
        $category->update($data);

        return redirect()->route('admin.assets.categories.index')->with('success', 'Asset category updated.');
    }

    public function destroy(AssetCategory $category)
    {
        abort_unless(Auth::guard('admin')->user()->can('asset_categories.delete'), 403);

        if ($category->assets()->exists() || $category->models()->exists()) {
            return back()->with('error', 'Cannot delete category — it is referenced by assets or models. Mark it inactive instead.');
        }
        $category->delete();

        return back()->with('success', 'Asset category deleted.');
    }

    protected function validateData(Request $request, ?int $id = null): array
    {
        $unique = $id ? ',code,'.$id : ',code';

        return $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:asset_categories'.$unique],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'default_depreciation_method' => ['required', 'in:straight_line,declining_balance,sum_of_years_digits,units_of_production'],
            'default_useful_life_years' => ['required', 'integer', 'min:1', 'max:60'],
            'default_salvage_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'status' => ['required', 'in:active,inactive'],
        ]);
    }
}
