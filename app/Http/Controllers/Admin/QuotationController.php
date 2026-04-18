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

        $quotationItems = $quotation->items->map(fn($i) => [
            'product_id'       => $i->product_id ?? '',
            'description'      => $i->description ?? '',
            'hsn_code'         => $i->hsn_code ?? '',
            'quantity'         => $i->quantity ?? 1,
            'unit'             => $i->unit ?? 'pcs',
            'rate'             => $i->rate ?? 0,
            'discount_percent' => $i->discount_percent ?? 0,
            'tax_percent'      => $i->tax_percent ?? 0,
            'line_total'       => $i->line_total ?? 0,
        ])->values();

        return view('admin.quotations.edit', compact('quotation', 'customers', 'products', 'quotationItems'));
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

        $pdfSubtotal = 0;
        foreach ($quotation->items as $item) {
            $g = floatval($item->quantity ?? 0) * floatval($item->rate ?? 0);
            $a = $g - $g * (floatval($item->discount_percent ?? 0) / 100);
            $pdfSubtotal += $a + $a * (floatval($item->tax_percent ?? 0) / 100);
        }
        $pdfSubtotal   = round($pdfSubtotal, 2);
        $pdfDiscVal    = floatval($quotation->discount_value ?? 0);
        $pdfDiscAmt    = $quotation->discount_type === 'percent'
            ? round($pdfSubtotal * $pdfDiscVal / 100, 2)
            : round($pdfDiscVal, 2);
        $pdfAfterDisc  = $pdfSubtotal - $pdfDiscAmt;
        $pdfTaxAmt     = round($pdfAfterDisc * (floatval($quotation->tax_percent ?? 0) / 100), 2);
        $pdfGrandTotal = round($pdfAfterDisc + $pdfTaxAmt, 2);

        $pdf = Pdf::loadView('admin.quotations.pdf', compact(
            'quotation', 'pdfSubtotal', 'pdfDiscVal', 'pdfDiscAmt', 'pdfTaxAmt', 'pdfGrandTotal'
        ));

        return $pdf->stream("Quotation-{$quotation->quotation_number}.pdf");
    }

    public function updateStatus(Request $request, $id)
    {
        abort_unless(Auth::guard('admin')->user()->can('quotations.edit'), 403);

        $quotation = Quotation::findOrFail($id);

        $allowed = ['draft', 'sent', 'accepted', 'rejected', 'expired'];
        $request->validate(['status' => 'required|in:' . implode(',', $allowed)]);

        $transitions = [
            'draft'    => ['sent'],
            'sent'     => ['accepted', 'rejected', 'expired'],
            'accepted' => [],
            'rejected' => ['draft'],
            'expired'  => ['draft'],
        ];

        if (!in_array($request->status, $transitions[$quotation->status] ?? [])) {
            $msg = "Cannot change status from '{$quotation->status}' to '{$request->status}'.";
            if ($request->ajax()) return response()->json(['success' => false, 'message' => $msg], 422);
            return redirect()->back()->with('error', $msg);
        }

        $quotation->update(['status' => $request->status]);

        $msg = 'Quotation marked as ' . ucfirst($request->status) . '.';
        if ($request->ajax()) return response()->json(['success' => true, 'message' => $msg]);
        return redirect()->back()->with('success', $msg);
    }

    public function clone($id)
    {
        abort_unless(Auth::guard('admin')->user()->can('quotations.create'), 403);

        $quotation = Quotation::with('items')->findOrFail($id);
        $newQuotation = $this->quotationService->clone($quotation);

        if (request()->ajax()) {
            return response()->json(['success' => true, 'message' => "Quotation cloned. New number: {$newQuotation->quotation_number}"]);
        }

        return redirect()->route('admin.quotations.edit', $newQuotation->id)
            ->with('success', "Quotation cloned successfully. New number: {$newQuotation->quotation_number}");
    }

    public function convertToOrder($id)
    {
        abort_unless(Auth::guard('admin')->user()->can('quotations.edit'), 403);

        $quotation = Quotation::with('items')->findOrFail($id);

        if ($quotation->status === 'accepted') {
            if (request()->ajax()) {
                return response()->json(['success' => false, 'message' => 'This quotation has already been converted.'], 422);
            }
            return redirect()->back()->with('error', 'This quotation has already been converted to a sales order.');
        }

        $salesOrder = $this->quotationService->convertToSalesOrder($quotation);

        if (request()->ajax()) {
            return response()->json(['success' => true, 'message' => "Converted to Sales Order #{$salesOrder->order_number}."]);
        }

        return redirect()->route('admin.sales-orders.show', $salesOrder->id)
            ->with('success', "Quotation converted to Sales Order #{$salesOrder->order_number} successfully.");
    }
}
