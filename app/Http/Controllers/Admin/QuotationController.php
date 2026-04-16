<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreQuotationRequest;
use App\Http\Requests\Admin\UpdateQuotationRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Quotation;
use App\Services\QuotationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuotationController extends Controller
{
    public function __construct(
        protected QuotationService $quotationService,
    ) {}

    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('quotations.view'), 403);

        $quotations = Quotation::with(['customer'])
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('quotation_number', 'like', "%{$s}%")
                    ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$s}%"));
            }))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->customer_id, fn ($q, $c) => $q->where('customer_id', $c))
            ->latest()
            ->paginate(10);

        if ($request->ajax()) {
            return response()->json([
                'data' => $quotations->items(),
                'pagination' => [
                    'total' => $quotations->total(),
                    'per_page' => $quotations->perPage(),
                    'current_page' => $quotations->currentPage(),
                    'last_page' => $quotations->lastPage(),
                    'from' => $quotations->firstItem() ?? 0,
                    'to' => $quotations->lastItem() ?? 0,
                ],
            ]);
        }

        $customers = Customer::where('status', 'active')->orderBy('name')->get();

        return view('admin.quotations.index', compact('quotations', 'customers'));
    }

    public function create()
    {
        abort_unless(Auth::guard('admin')->user()->can('quotations.create'), 403);

        $customers = Customer::where('status', 'active')->orderBy('name')->get();
        $products = Product::where('status', 'active')->orderBy('name')->get();

        return view('admin.quotations.create', compact('customers', 'products'));
    }

    public function store(StoreQuotationRequest $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('quotations.create'), 403);

        $data = $request->except('items');
        $items = $request->input('items', []);

        $this->quotationService->create($data, $items);

        return redirect()->route('admin.quotations.index')->with('success', 'Quotation created successfully.');
    }

    public function show($id)
    {
        abort_unless(Auth::guard('admin')->user()->can('quotations.view'), 403);

        $quotation = Quotation::with(['customer', 'items.product', 'salesOrder', 'creator'])->findOrFail($id);

        return view('admin.quotations.show', compact('quotation'));
    }

    public function edit($id)
    {
        abort_unless(Auth::guard('admin')->user()->can('quotations.edit'), 403);

        $quotation = Quotation::with('items')->findOrFail($id);
        $customers = Customer::where('status', 'active')->orderBy('name')->get();
        $products = Product::where('status', 'active')->orderBy('name')->get();

        return view('admin.quotations.edit', compact('quotation', 'customers', 'products'));
    }

    public function update(UpdateQuotationRequest $request, $id)
    {
        abort_unless(Auth::guard('admin')->user()->can('quotations.edit'), 403);

        $quotation = Quotation::findOrFail($id);
        $data = $request->except('items');
        $items = $request->input('items', []);

        $this->quotationService->update($quotation, $data, $items);

        return redirect()->route('admin.quotations.index')->with('success', 'Quotation updated successfully.');
    }

    public function destroy(Request $request, $id)
    {
        abort_unless(Auth::guard('admin')->user()->can('quotations.delete'), 403);

        $quotation = Quotation::findOrFail($id);
        $this->quotationService->delete($quotation);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Quotation deleted successfully.']);
        }

        return redirect()->route('admin.quotations.index')->with('success', 'Quotation deleted successfully.');
    }

    public function pdf($id)
    {
        abort_unless(Auth::guard('admin')->user()->can('quotations.view'), 403);

        $quotation = Quotation::with(['customer', 'items.product', 'creator'])->findOrFail($id);

        $pdf = Pdf::loadView('admin.quotations.pdf', compact('quotation'));

        return $pdf->download("Quotation-{$quotation->quotation_number}.pdf");
    }

    public function clone($id)
    {
        abort_unless(Auth::guard('admin')->user()->can('quotations.create'), 403);

        $quotation = Quotation::with('items')->findOrFail($id);
        $newQuotation = $this->quotationService->clone($quotation);

        return redirect()->route('admin.quotations.edit', $newQuotation->id)
            ->with('success', "Quotation cloned successfully. New number: {$newQuotation->quotation_number}");
    }

    public function convertToOrder($id)
    {
        abort_unless(Auth::guard('admin')->user()->can('quotations.edit'), 403);

        $quotation = Quotation::with('items')->findOrFail($id);

        if ($quotation->status === 'accepted') {
            return redirect()->back()->with('error', 'This quotation has already been converted to a sales order.');
        }

        $salesOrder = $this->quotationService->convertToSalesOrder($quotation);

        return redirect()->route('admin.sales-orders.show', $salesOrder->id)
            ->with('success', "Quotation converted to Sales Order #{$salesOrder->order_number} successfully.");
    }
}
