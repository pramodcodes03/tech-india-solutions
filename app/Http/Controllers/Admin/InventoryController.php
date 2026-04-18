<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory as Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InventoryController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService,
    ) {}

    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('inventory.view'), 403);

        $inventories = StockMovement::with(['product.category', 'warehouse'])
            ->selectRaw('product_id, warehouse_id, COALESCE(SUM(CASE WHEN type IN (\'in\', \'adjustment\') THEN quantity ELSE -quantity END), 0) as quantity')
            ->groupBy('product_id', 'warehouse_id')
            ->when($request->warehouse_id, fn ($q, $w) => $q->where('warehouse_id', $w))
            ->when($request->category_id, fn ($q, $c) => $q->whereHas('product', fn ($pq) => $pq->where('category_id', $c)))
            ->when($request->search, fn ($q, $s) => $q->whereHas('product', fn ($pq) => $pq->where(function ($pq) use ($s) {
                $pq->where('name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%");
            })))
            ->whereHas('product', fn ($q) => $q->where('status', 'active'))
            ->paginate(10);

        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();
        $categories = Category::orderBy('name')->get();

        return view('admin.inventory.index', compact('inventories', 'warehouses', 'categories'));
    }

    public function movements(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('inventory.view'), 403);

        $movements = StockMovement::with(['product', 'warehouse', 'creator'])
            ->when($request->product_id, fn ($q, $p) => $q->where('product_id', $p))
            ->when($request->warehouse_id, fn ($q, $w) => $q->where('warehouse_id', $w))
            ->when($request->type, fn ($q, $t) => $q->where('type', $t))
            ->when($request->date_from, fn ($q, $d) => $q->where('created_at', '>=', $d))
            ->when($request->date_to, fn ($q, $d) => $q->where('created_at', '<=', $d.' 23:59:59'))
            ->latest()
            ->paginate(10);

        if ($request->ajax()) {
            return response()->json([
                'data' => $movements->items(),
                'pagination' => [
                    'total' => $movements->total(),
                    'per_page' => $movements->perPage(),
                    'current_page' => $movements->currentPage(),
                    'last_page' => $movements->lastPage(),
                    'from' => $movements->firstItem() ?? 0,
                    'to' => $movements->lastItem() ?? 0,
                ],
            ]);
        }

        $products = Product::where('status', 'active')->orderBy('name')->get();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        return view('admin.inventory.movements', compact('movements', 'products', 'warehouses'));
    }

    public function adjust()
    {
        abort_unless(Auth::guard('admin')->user()->can('inventory.adjust'), 403);

        $products = Product::where('status', 'active')->orderBy('name')->get();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        return view('admin.inventory.adjust', compact('products', 'warehouses'));
    }

    public function storeAdjustment(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('inventory.adjust'), 403);

        $request->validate([
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'quantity' => 'required|numeric',
            'notes' => 'required|string|max:500',
        ]);

        $this->inventoryService->adjustStock(
            $request->product_id,
            $request->warehouse_id,
            $request->quantity,
            $request->notes,
        );

        return redirect()->route('admin.inventory.movements')->with('success', 'Stock adjustment recorded successfully.');
    }

    public function lowStock(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('inventory.view'), 403);

        $lowStockItems = $this->inventoryService->getLowStockProducts();

        return view('admin.inventory.low-stock', compact('lowStockItems'));
    }
}
