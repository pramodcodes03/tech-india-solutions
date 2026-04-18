<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreInvoiceRequest;
use App\Http\Requests\Admin\UpdateInvoiceRequest;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Services\InvoiceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    public function __construct(
        protected InvoiceService $invoiceService,
    ) {}

    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('invoices.view'), 403);

        $invoices = Invoice::with(['customer'])
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('invoice_number', 'like', "%{$s}%")
                    ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$s}%"));
            }))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->customer_id, fn ($q, $c) => $q->where('customer_id', $c))
            ->latest()
            ->paginate(10);

        if ($request->ajax()) {
            return response()->json([
                'data' => $invoices->items(),
                'pagination' => [
                    'total' => $invoices->total(),
                    'per_page' => $invoices->perPage(),
                    'current_page' => $invoices->currentPage(),
                    'last_page' => $invoices->lastPage(),
                    'from' => $invoices->firstItem() ?? 0,
                    'to' => $invoices->lastItem() ?? 0,
                ],
            ]);
        }

        $customers = Customer::where('status', 'active')->orderBy('name')->get();

        return view('admin.invoices.index', compact('invoices', 'customers'));
    }

    public function create()
    {
        abort_unless(Auth::guard('admin')->user()->can('invoices.create'), 403);

        $customers = Customer::where('status', 'active')->orderBy('name')->get();
        $products = Product::where('status', 'active')->orderBy('name')->get();
        $salesOrders = SalesOrder::whereNotIn('status', ['cancelled'])
            ->orderByDesc('order_date')
            ->get();

        return view('admin.invoices.create', compact('customers', 'products', 'salesOrders'));
    }

    public function store(StoreInvoiceRequest $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('invoices.create'), 403);

        $data = $request->except('items');
        $items = $request->input('items', []);

        $this->invoiceService->create($data, $items);

        return redirect()->route('admin.invoices.index')->with('success', 'Invoice created successfully.');
    }

    public function show($id)
    {
        abort_unless(Auth::guard('admin')->user()->can('invoices.view'), 403);

        $invoice = Invoice::with([
            'customer',
            'items.product',
            'salesOrder',
            'payments',
            'creator',
        ])->findOrFail($id);

        return view('admin.invoices.show', compact('invoice'));
    }

    public function edit($id)
    {
        abort_unless(Auth::guard('admin')->user()->can('invoices.edit'), 403);

        $invoice = Invoice::with('items')->findOrFail($id);
        $customers = Customer::where('status', 'active')->orderBy('name')->get();
        $products = Product::where('status', 'active')->orderBy('name')->get();

        return view('admin.invoices.edit', compact('invoice', 'customers', 'products'));
    }

    public function update(UpdateInvoiceRequest $request, $id)
    {
        abort_unless(Auth::guard('admin')->user()->can('invoices.edit'), 403);

        $invoice = Invoice::findOrFail($id);
        $data = $request->except('items');
        $items = $request->input('items', []);

        $this->invoiceService->update($invoice, $data, $items);

        return redirect()->route('admin.invoices.index')->with('success', 'Invoice updated successfully.');
    }

    public function destroy(Request $request, $id)
    {
        abort_unless(Auth::guard('admin')->user()->can('invoices.delete'), 403);

        $invoice = Invoice::findOrFail($id);
        $this->invoiceService->delete($invoice);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Invoice deleted successfully.']);
        }

        return redirect()->route('admin.invoices.index')->with('success', 'Invoice deleted successfully.');
    }

    public function pdf($id)
    {
        abort_unless(Auth::guard('admin')->user()->can('invoices.view'), 403);

        $invoice = Invoice::with(['customer', 'items.product', 'creator'])->findOrFail($id);

        $pdf = Pdf::loadView('admin.invoices.pdf', compact('invoice'));

        return $pdf->stream("Invoice-{$invoice->invoice_number}.pdf");
    }
}
