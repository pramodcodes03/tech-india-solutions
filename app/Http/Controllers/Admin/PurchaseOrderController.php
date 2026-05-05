<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGoodsReceiptRequest;
use App\Http\Requests\Admin\StorePurchaseOrderRequest;
use App\Http\Requests\Admin\UpdatePurchaseOrderRequest;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Vendor;
use App\Services\PurchaseOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PurchaseOrderController extends Controller
{
    public function __construct(
        protected PurchaseOrderService $purchaseOrderService,
    ) {}

    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('purchase_orders.view'), 403);

        $purchaseOrders = PurchaseOrder::with(['vendor'])
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('po_number', 'like', "%{$s}%")
                    ->orWhereHas('vendor', fn ($vq) => $vq->where('name', 'like', "%{$s}%"));
            }))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->vendor_id, fn ($q, $v) => $q->where('vendor_id', $v))
            ->latest()
            ->paginate(10);

        if ($request->ajax()) {
            return response()->json([
                'data' => $purchaseOrders->items(),
                'pagination' => [
                    'total' => $purchaseOrders->total(),
                    'per_page' => $purchaseOrders->perPage(),
                    'current_page' => $purchaseOrders->currentPage(),
                    'last_page' => $purchaseOrders->lastPage(),
                    'from' => $purchaseOrders->firstItem() ?? 0,
                    'to' => $purchaseOrders->lastItem() ?? 0,
                ],
            ]);
        }

        $vendors = Vendor::where('status', 'active')->orderBy('name')->get();

        return view('admin.purchase-orders.index', compact('purchaseOrders', 'vendors'));
    }

    public function create()
    {
        abort_unless(Auth::guard('admin')->user()->can('purchase_orders.create'), 403);

        $vendors = Vendor::where('status', 'active')->orderBy('name')->get();
        $products = Product::where('status', 'active')->orderBy('name')->get();

        return view('admin.purchase-orders.create', compact('vendors', 'products'));
    }

    public function store(StorePurchaseOrderRequest $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('purchase_orders.create'), 403);

        $data = $request->except('items');
        $items = $request->input('items', []);

        $po = $this->purchaseOrderService->create($data, $items);

        \App\Notifications\NotificationDispatcher::fire('purchase_order.issued', $po->loadMissing('vendor'));

        return redirect()->route('admin.purchase-orders.index')->with('success', 'Purchase order created successfully.');
    }

    public function show($id)
    {
        abort_unless(Auth::guard('admin')->user()->can('purchase_orders.view'), 403);

        $purchaseOrder = PurchaseOrder::with([
            'vendor',
            'items.product',
            'goodsReceipts.items',
            'creator',
        ])->findOrFail($id);

        return view('admin.purchase-orders.show', compact('purchaseOrder'));
    }

    public function edit($id)
    {
        abort_unless(Auth::guard('admin')->user()->can('purchase_orders.edit'), 403);

        $purchaseOrder = PurchaseOrder::with('items')->findOrFail($id);
        $vendors = Vendor::where('status', 'active')->orderBy('name')->get();
        $products = Product::where('status', 'active')->orderBy('name')->get();

        return view('admin.purchase-orders.edit', compact('purchaseOrder', 'vendors', 'products'));
    }

    public function update(UpdatePurchaseOrderRequest $request, $id)
    {
        abort_unless(Auth::guard('admin')->user()->can('purchase_orders.edit'), 403);

        $purchaseOrder = PurchaseOrder::findOrFail($id);
        $data = $request->except('items');
        $items = $request->input('items', []);

        $this->purchaseOrderService->update($purchaseOrder, $data, $items);

        return redirect()->route('admin.purchase-orders.index')->with('success', 'Purchase order updated successfully.');
    }

    public function destroy(Request $request, $id)
    {
        abort_unless(Auth::guard('admin')->user()->can('purchase_orders.delete'), 403);

        $purchaseOrder = PurchaseOrder::findOrFail($id);
        $this->purchaseOrderService->delete($purchaseOrder);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Purchase order deleted successfully.']);
        }

        return redirect()->route('admin.purchase-orders.index')->with('success', 'Purchase order deleted successfully.');
    }

    public function receiveGoods(StoreGoodsReceiptRequest $request, $id)
    {
        abort_unless(Auth::guard('admin')->user()->can('purchase_orders.edit'), 403);

        $purchaseOrder = PurchaseOrder::with('items')->findOrFail($id);

        $grn = $this->purchaseOrderService->receiveGoods(
            $purchaseOrder,
            $request->input('items', []),
            $request->input('notes', ''),
        );

        \App\Notifications\NotificationDispatcher::fire(
            'goods_receipt.received',
            $grn->loadMissing('purchaseOrder'),
        );

        return redirect()->route('admin.purchase-orders.show', $purchaseOrder->id)
            ->with('success', "Goods received successfully. GRN #{$grn->grn_number}");
    }
}
