<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProformaInvoiceRequest;
use App\Http\Requests\Admin\UpdateProformaInvoiceRequest;
use App\Models\Customer;
use App\Models\ProformaInvoice;
use App\Models\Product;
use App\Models\Quotation;
use App\Services\ProformaInvoiceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProformaInvoiceController extends Controller
{
    public function __construct(
        protected ProformaInvoiceService $service,
    ) {}

    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('proforma_invoices.view'), 403);

        $proformas = ProformaInvoice::with(['customer', 'invoice'])
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('proforma_number', 'like', "%{$s}%")
                    ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$s}%"));
            }))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->customer_id, fn ($q, $c) => $q->where('customer_id', $c))
            ->latest()
            ->paginate(10);

        if ($request->ajax()) {
            return response()->json([
                'data' => $proformas->items(),
                'pagination' => [
                    'total' => $proformas->total(),
                    'per_page' => $proformas->perPage(),
                    'current_page' => $proformas->currentPage(),
                    'last_page' => $proformas->lastPage(),
                    'from' => $proformas->firstItem() ?? 0,
                    'to' => $proformas->lastItem() ?? 0,
                ],
            ]);
        }

        $customers = Customer::where('status', 'active')->orderBy('name')->get();

        return view('admin.proforma-invoices.index', compact('proformas', 'customers'));
    }

    public function create()
    {
        abort_unless(Auth::guard('admin')->user()->can('proforma_invoices.create'), 403);

        $customers = Customer::where('status', 'active')->orderBy('name')->get();
        $products = Product::where('status', 'active')->orderBy('name')->get();
        $quotations = Quotation::whereIn('status', ['draft', 'sent', 'accepted'])
            ->orderByDesc('quotation_date')
            ->get();

        return view('admin.proforma-invoices.create', compact('customers', 'products', 'quotations'));
    }

    public function store(StoreProformaInvoiceRequest $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('proforma_invoices.create'), 403);

        $data = $request->except('items');
        $items = $request->input('items', []);

        $this->service->create($data, $items);

        return redirect()->route('admin.proforma-invoices.index')->with('success', 'Proforma invoice created successfully.');
    }

    public function show($id)
    {
        abort_unless(Auth::guard('admin')->user()->can('proforma_invoices.view'), 403);

        $proforma = ProformaInvoice::with(['customer', 'items.product', 'invoice', 'quotation', 'creator'])->findOrFail($id);

        return view('admin.proforma-invoices.show', compact('proforma'));
    }

    public function edit($id)
    {
        abort_unless(Auth::guard('admin')->user()->can('proforma_invoices.edit'), 403);

        $proforma = ProformaInvoice::with('items')->findOrFail($id);
        $customers = Customer::where('status', 'active')->orderBy('name')->get();
        $products = Product::where('status', 'active')->orderBy('name')->get();

        $proformaItems = $proforma->items->map(fn ($i) => [
            'product_id' => $i->product_id ?? '',
            'description' => $i->description ?? '',
            'hsn_code' => $i->hsn_code ?? '',
            'quantity' => $i->quantity ?? 1,
            'unit' => $i->unit ?? 'pcs',
            'rate' => $i->rate ?? 0,
            'discount_percent' => $i->discount_percent ?? 0,
            'tax_percent' => $i->tax_percent ?? 0,
            'line_total' => $i->line_total ?? 0,
        ])->values();

        return view('admin.proforma-invoices.edit', compact('proforma', 'customers', 'products', 'proformaItems'));
    }

    public function update(UpdateProformaInvoiceRequest $request, $id)
    {
        abort_unless(Auth::guard('admin')->user()->can('proforma_invoices.edit'), 403);

        $proforma = ProformaInvoice::findOrFail($id);
        $data = $request->except('items');
        $items = $request->input('items', []);

        $this->service->update($proforma, $data, $items);

        return redirect()->route('admin.proforma-invoices.index')->with('success', 'Proforma invoice updated successfully.');
    }

    public function destroy(Request $request, $id)
    {
        abort_unless(Auth::guard('admin')->user()->can('proforma_invoices.delete'), 403);

        $proforma = ProformaInvoice::findOrFail($id);
        $this->service->delete($proforma);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Proforma invoice deleted successfully.']);
        }

        return redirect()->route('admin.proforma-invoices.index')->with('success', 'Proforma invoice deleted successfully.');
    }

    public function pdf($id)
    {
        abort_unless(Auth::guard('admin')->user()->can('proforma_invoices.view'), 403);

        $proforma = ProformaInvoice::with(['customer', 'items.product', 'creator'])->findOrFail($id);

        $pdfSubtotal = 0;
        foreach ($proforma->items as $item) {
            $g = floatval($item->quantity ?? 0) * floatval($item->rate ?? 0);
            $a = $g - $g * (floatval($item->discount_percent ?? 0) / 100);
            $pdfSubtotal += $a + $a * (floatval($item->tax_percent ?? 0) / 100);
        }
        $pdfSubtotal = round($pdfSubtotal, 2);
        $pdfDiscVal = floatval($proforma->discount_value ?? 0);
        $pdfDiscAmt = $proforma->discount_type === 'percent'
            ? round($pdfSubtotal * $pdfDiscVal / 100, 2)
            : round($pdfDiscVal, 2);
        $pdfAfterDisc = $pdfSubtotal - $pdfDiscAmt;
        $pdfTaxAmt = round($pdfAfterDisc * (floatval($proforma->tax_percent ?? 0) / 100), 2);
        $pdfGrandTotal = round($pdfAfterDisc + $pdfTaxAmt, 2);

        $pdf = Pdf::loadView('admin.proforma-invoices.pdf', compact(
            'proforma', 'pdfSubtotal', 'pdfDiscVal', 'pdfDiscAmt', 'pdfTaxAmt', 'pdfGrandTotal'
        ));

        return $pdf->stream("Proforma-{$proforma->proforma_number}.pdf");
    }

    public function updateStatus(Request $request, $id)
    {
        abort_unless(Auth::guard('admin')->user()->can('proforma_invoices.edit'), 403);

        $proforma = ProformaInvoice::findOrFail($id);

        $allowed = ['draft', 'sent', 'accepted', 'rejected', 'expired'];
        $request->validate(['status' => 'required|in:'.implode(',', $allowed)]);

        $transitions = [
            'draft' => ['sent'],
            'sent' => ['accepted', 'rejected', 'expired'],
            'accepted' => [],
            'rejected' => ['draft'],
            'expired' => ['draft'],
            'converted' => [],
        ];

        if (! in_array($request->status, $transitions[$proforma->status] ?? [])) {
            $msg = "Cannot change status from '{$proforma->status}' to '{$request->status}'.";
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return redirect()->back()->with('error', $msg);
        }

        $proforma->update(['status' => $request->status]);

        $msg = 'Proforma invoice marked as '.ucfirst($request->status).'.';
        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return redirect()->back()->with('success', $msg);
    }

    public function clone($id)
    {
        abort_unless(Auth::guard('admin')->user()->can('proforma_invoices.create'), 403);

        $proforma = ProformaInvoice::with('items')->findOrFail($id);
        $new = $this->service->clone($proforma);

        if (request()->ajax()) {
            return response()->json(['success' => true, 'message' => "Proforma cloned. New number: {$new->proforma_number}"]);
        }

        return redirect()->route('admin.proforma-invoices.edit', $new->id)
            ->with('success', "Proforma cloned successfully. New number: {$new->proforma_number}");
    }

    public function convertToInvoice($id)
    {
        abort_unless(Auth::guard('admin')->user()->can('proforma_invoices.edit'), 403);

        $proforma = ProformaInvoice::with('items')->findOrFail($id);

        if ($proforma->status === 'converted' || $proforma->invoice_id) {
            $msg = 'This proforma has already been converted to a tax invoice.';
            if (request()->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return redirect()->back()->with('error', $msg);
        }

        $invoice = $this->service->convertToInvoice($proforma);

        if (request()->ajax()) {
            return response()->json(['success' => true, 'message' => "Converted to Invoice #{$invoice->invoice_number}."]);
        }

        return redirect()->route('admin.invoices.show', $invoice->id)
            ->with('success', "Proforma converted to Tax Invoice #{$invoice->invoice_number} successfully.");
    }
}
