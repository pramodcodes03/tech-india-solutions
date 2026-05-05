<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePaymentRequest;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService,
    ) {}

    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('payments.view'), 403);

        $payments = Payment::with(['invoice', 'customer'])
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('payment_number', 'like', "%{$s}%")
                    ->orWhere('reference_no', 'like', "%{$s}%")
                    ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$s}%"));
            }))
            ->when($request->customer_id, fn ($q, $c) => $q->where('customer_id', $c))
            ->when($request->mode, fn ($q, $m) => $q->where('mode', $m))
            ->when($request->date_from, fn ($q, $d) => $q->where('payment_date', '>=', $d))
            ->when($request->date_to, fn ($q, $d) => $q->where('payment_date', '<=', $d))
            ->latest()
            ->paginate(10);

        if ($request->ajax()) {
            return response()->json([
                'data' => $payments->items(),
                'pagination' => [
                    'total' => $payments->total(),
                    'per_page' => $payments->perPage(),
                    'current_page' => $payments->currentPage(),
                    'last_page' => $payments->lastPage(),
                    'from' => $payments->firstItem() ?? 0,
                    'to' => $payments->lastItem() ?? 0,
                ],
            ]);
        }

        $customers = Customer::where('status', 'active')->orderBy('name')->get();

        return view('admin.payments.index', compact('payments', 'customers'));
    }

    public function create()
    {
        abort_unless(Auth::guard('admin')->user()->can('payments.create'), 403);

        $customers = Customer::where('status', 'active')->orderBy('name')->get();
        $invoices = Invoice::whereIn('status', ['unpaid', 'partial'])
            ->with('customer:id,name')
            ->orderByDesc('invoice_date')
            ->get()
            ->map(fn ($inv) => [
                'id'             => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'customer_name'  => $inv->customer?->name ?? '-',
                'grand_total'    => (float) $inv->grand_total,
                'amount_paid'    => (float) $inv->amount_paid,
                'balance'        => (float) $inv->balance_due,
            ])
            ->values();

        return view('admin.payments.create', compact('customers', 'invoices'));
    }

    public function store(StorePaymentRequest $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('payments.create'), 403);

        $data = $request->validated();

        $invoice = Invoice::findOrFail($data['invoice_id']);
        $data['customer_id'] = $invoice->customer_id;

        $payment = $this->paymentService->create($data);

        \App\Notifications\NotificationDispatcher::fire('payment.received', $payment->loadMissing('customer'), [
            'invoice_number' => $invoice->invoice_number,
        ]);

        return redirect()->route('admin.payments.index')->with('success', 'Payment recorded successfully.');
    }

    public function show($id)
    {
        abort_unless(Auth::guard('admin')->user()->can('payments.view'), 403);

        $payment = Payment::with(['invoice.customer', 'customer', 'creator'])->findOrFail($id);

        return view('admin.payments.show', compact('payment'));
    }

    public function destroy(Request $request, $id)
    {
        abort_unless(Auth::guard('admin')->user()->can('payments.delete'), 403);

        $payment = Payment::findOrFail($id);
        $this->paymentService->delete($payment);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Payment deleted successfully.']);
        }

        return redirect()->route('admin.payments.index')->with('success', 'Payment deleted successfully.');
    }
}
