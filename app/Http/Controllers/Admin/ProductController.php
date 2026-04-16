<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $productService,
    ) {}

    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('products.view'), 403);

        $products = Product::with(['category'])
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('code', 'like', "%{$s}%")
                    ->orWhere('hsn_code', 'like', "%{$s}%");
            }))
            ->when($request->category_id, fn ($q, $c) => $q->where('category_id', $c))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(10);

        if ($request->ajax()) {
            return response()->json([
                'data' => $products->items(),
                'pagination' => [
                    'total' => $products->total(),
                    'per_page' => $products->perPage(),
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'from' => $products->firstItem() ?? 0,
                    'to' => $products->lastItem() ?? 0,
                ],
            ]);
        }

        $categories = ProductCategory::where('is_active', true)->orderBy('name')->get();

        return view('admin.products.index', compact('products', 'categories'));
    }

    public function create()
    {
        abort_unless(Auth::guard('admin')->user()->can('products.create'), 403);

        $categories = ProductCategory::where('is_active', true)->orderBy('name')->get();

        return view('admin.products.create', compact('categories'));
    }

    public function store(StoreProductRequest $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('products.create'), 403);

        $this->productService->create($request->validated());

        return redirect()->route('admin.products.index')->with('success', 'Product created successfully.');
    }

    public function show($id)
    {
        abort_unless(Auth::guard('admin')->user()->can('products.view'), 403);

        $product = Product::with(['category', 'stockMovements.warehouse', 'creator'])->findOrFail($id);

        return view('admin.products.show', compact('product'));
    }

    public function edit($id)
    {
        abort_unless(Auth::guard('admin')->user()->can('products.edit'), 403);

        $product = Product::findOrFail($id);
        $categories = ProductCategory::where('is_active', true)->orderBy('name')->get();

        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(UpdateProductRequest $request, $id)
    {
        abort_unless(Auth::guard('admin')->user()->can('products.edit'), 403);

        $product = Product::findOrFail($id);
        $this->productService->update($product, $request->validated());

        return redirect()->route('admin.products.index')->with('success', 'Product updated successfully.');
    }

    public function destroy(Request $request, $id)
    {
        abort_unless(Auth::guard('admin')->user()->can('products.delete'), 403);

        $product = Product::findOrFail($id);
        $this->productService->delete($product);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Product deleted successfully.']);
        }

        return redirect()->route('admin.products.index')->with('success', 'Product deleted successfully.');
    }

    public function toggleStatus(Request $request, $id)
    {
        abort_unless(Auth::guard('admin')->user()->can('products.edit'), 403);

        $product = Product::findOrFail($id);
        $product->update(['status' => $product->status === 'active' ? 'inactive' : 'active']);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
        }

        return redirect()->back()->with('success', 'Product status updated.');
    }
}
