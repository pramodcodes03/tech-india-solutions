<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSalesOrderRequest;
use App\Http\Requests\Admin\UpdateSalesOrderRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Services\SalesOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SalesOrderController extends Controller
{
    public function __construct(
        protected SalesOrderService $salesOrderService,
    ) {}

    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('sales_orders.view'), 403);

        $salesOrders = SalesOrder::with(['customer'])
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('order_number', 'like', "%{$s}%")
                    ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$s}%"));
            }))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->customer_id, fn ($q, $c) => $q->where('customer_id', $c))
            ->latest()
            ->paginate(10);

        if ($request->ajax()) {
            return response()->json([
                'data' => $salesOrders->items(),
                'pagination' => [
                    'total' => $salesOrders->total(),
                    'per_page' => $salesOrders->perPage(),
                    'current_page' => $salesOrders->currentPage(),
                    'last_page' => $salesOrders->lastPage(),
                    'from' => $salesOrders->firstItem() ?? 0,
                    'to' => $salesOrders->lastItem() ?? 0,
                ],
            ]);
        }

        $customers = Customer::where('status', 'active')->orderBy('name')->get();

        return view('admin.sales-orders.index', compact('salesOrders', 'customers'));
    }

    public function create()
    {
        abort_unless(Auth::guard('admin')->user()->can('sales_orders.create'), 403);

        $customers = Customer::where('status', 'active')->orderBy('name')->get();
        $products = Product::where('status', 'active')->orderBy('name')->get();

        return view('admin.sales-orders.create', compact('customers', 'products'));
    }

    public function store(StoreSalesOrderRequest $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('sales_orders.create'), 403);

        $data = $request->except('items');
        $items = $request->input('items', []);

        $this->salesOrderService->create($data, $items);

        return redirect()->route('admin.sales-orders.index')->with('success', 'Sales order created successfully.');
    }

    public function show($id)
    {
        abort_unless(Auth::guard('admin')->user()->can('sales_orders.view'), 403);

        $salesOrder = SalesOrder::with(['customer', 'items.product', 'quotation', 'invoices', 'creator'])->findOrFail($id);

        return view('admin.sales-orders.show', compact('salesOrder'));
    }

    public function edit($id)
    {
        abort_unless(Auth::guard('admin')->user()->can('sales_orders.edit'), 403);

        $salesOrder = SalesOrder::with('items')->findOrFail($id);

        if (in_array($salesOrder->status, ['delivered', 'cancelled'])) {
            return redirect()->back()->with('error', "Cannot edit a sales order with status '{$salesOrder->status}'.");
        }

        $customers = Customer::where('status', 'active')->orderBy('name')->get();
        $products = Product::where('status', 'active')->orderBy('name')->get();

        return view('admin.sales-orders.edit', compact('salesOrder', 'customers', 'products'));
    }

    public function update(UpdateSalesOrderRequest $request, $id)
    {
        abort_unless(Auth::guard('admin')->user()->can('sales_orders.edit'), 403);

        $salesOrder = SalesOrder::findOrFail($id);
        $data = $request->except('items');
        $items = $request->input('items', []);

        try {
            $this->salesOrderService->update($salesOrder, $data, $items);
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.sales-orders.index')->with('success', 'Sales order updated successfully.');
    }

    public function destroy(Request $request, $id)
    {
        abort_unless(Auth::guard('admin')->user()->can('sales_orders.delete'), 403);

        $salesOrder = SalesOrder::findOrFail($id);
        $this->salesOrderService->delete($salesOrder);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Sales order deleted successfully.']);
        }

        return redirect()->route('admin.sales-orders.index')->with('success', 'Sales order deleted successfully.');
    }

    public function updateStatus(Request $request, $id)
    {
        abort_unless(Auth::guard('admin')->user()->can('sales_orders.edit'), 403);

        $request->validate([
            'status' => 'required|in:pending,confirmed,processing,shipped,delivered,cancelled',
        ]);

        $salesOrder = SalesOrder::findOrFail($id);

        try {
            $this->salesOrderService->updateStatus($salesOrder, $request->status);
        } catch (\RuntimeException $e) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Sales order status updated successfully.']);
        }

        return redirect()->back()->with('success', 'Sales order status updated successfully.');
    }

    public function generateInvoice($id)
    {
        abort_unless(Auth::guard('admin')->user()->can('sales_orders.edit'), 403);

        $salesOrder = SalesOrder::with('items')->findOrFail($id);

        $invoice = $this->salesOrderService->generateInvoice($salesOrder);

        return redirect()->route('admin.invoices.show', $invoice->id)
            ->with('success', "Invoice #{$invoice->invoice_number} generated successfully.");
    }
}
