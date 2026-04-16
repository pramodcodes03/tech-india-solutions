<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('categories.view'), 403);

        $categories = ProductCategory::with(['parent', 'children'])
            ->withCount('products')
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('sort_order')
            ->paginate(10);

        if ($request->ajax()) {
            return response()->json([
                'data' => $categories->items(),
                'pagination' => [
                    'total' => $categories->total(),
                    'per_page' => $categories->perPage(),
                    'current_page' => $categories->currentPage(),
                    'last_page' => $categories->lastPage(),
                    'from' => $categories->firstItem() ?? 0,
                    'to' => $categories->lastItem() ?? 0,
                ],
            ]);
        }

        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        abort_unless(Auth::guard('admin')->user()->can('categories.create'), 403);

        $parentCategories = ProductCategory::where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        return view('admin.categories.create', compact('parentCategories'));
    }

    public function store(StoreCategoryRequest $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('categories.create'), 403);

        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);
        $data['created_by'] = Auth::guard('admin')->id();

        ProductCategory::create($data);

        return redirect()->route('admin.categories.index')->with('success', 'Category created successfully.');
    }

    public function edit($id)
    {
        abort_unless(Auth::guard('admin')->user()->can('categories.edit'), 403);

        $category = ProductCategory::findOrFail($id);
        $parentCategories = ProductCategory::where('is_active', true)
            ->whereNull('parent_id')
            ->where('id', '!=', $id)
            ->orderBy('name')
            ->get();

        return view('admin.categories.edit', compact('category', 'parentCategories'));
    }

    public function update(UpdateCategoryRequest $request, $id)
    {
        abort_unless(Auth::guard('admin')->user()->can('categories.edit'), 403);

        $category = ProductCategory::findOrFail($id);

        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', $category->is_active);
        $data['updated_by'] = Auth::guard('admin')->id();

        $category->update($data);

        return redirect()->route('admin.categories.index')->with('success', 'Category updated successfully.');
    }

    public function destroy(Request $request, $id)
    {
        abort_unless(Auth::guard('admin')->user()->can('categories.delete'), 403);

        $category = ProductCategory::findOrFail($id);

        if ($category->products()->count() > 0) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Cannot delete a category that has products.'], 422);
            }

            return redirect()->back()->with('error', 'Cannot delete a category that has products.');
        }

        if ($category->children()->count() > 0) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Cannot delete a category that has sub-categories.'], 422);
            }

            return redirect()->back()->with('error', 'Cannot delete a category that has sub-categories.');
        }

        $category->update(['deleted_by' => Auth::guard('admin')->id()]);
        $category->delete();

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Category deleted successfully.']);
        }

        return redirect()->route('admin.categories.index')->with('success', 'Category deleted successfully.');
    }

    public function toggleStatus(Request $request, $id)
    {
        abort_unless(Auth::guard('admin')->user()->can('categories.edit'), 403);

        $category = ProductCategory::findOrFail($id);
        $category->update([
            'is_active' => ! $category->is_active,
            'updated_by' => Auth::guard('admin')->id(),
        ]);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
        }

        return redirect()->back()->with('success', 'Category status updated.');
    }
}
