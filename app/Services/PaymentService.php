<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        protected InvoiceService $invoiceService,
    ) {}

    /**
     * Generate the next payment number in PAY-YYYY-0001 format.
     */
    public function generateNumber(): string
    {
        $year = date('Y');
        $prefix = "PAY-{$year}-";
        $last = Payment::withTrashed()
            ->where('payment_number', 'like', $prefix.'%')
            ->orderByDesc('payment_number')
            ->first();

        $nextNumber = $last ? (int) substr($last->payment_number, strlen($prefix)) + 1 : 1;

        return $prefix.str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create a payment and recalculate the associated invoice totals.
     */
    public function create(array $data): Payment
    {
        return DB::transaction(function () use ($data) {
            $data['payment_number'] = $this->generateNumber();
            $data['created_by'] = Auth::guard('admin')->id();

            $payment = Payment::create($data);

            // Recalculate the invoice payment totals
            $this->invoiceService->recalculatePayments($payment->invoice);

            return $payment;
        });
    }

    /**
     * Delete a payment and recalculate the associated invoice totals.
     */
    public function delete(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            // A payment may have no (or a soft-deleted) invoice — load it without
            // global scopes so we can still recalc, and guard the null case so
            // deletion never 500s on an orphaned payment.
            $invoice = $payment->invoice
                ?? ($payment->invoice_id
                    ? \App\Models\Invoice::withoutGlobalScopes()->find($payment->invoice_id)
                    : null);

            $payment->update(['deleted_by' => Auth::guard('admin')->id()]);
            $payment->delete();

            // Recalculate the invoice payment totals after deletion, if linked.
            if ($invoice) {
                $this->invoiceService->recalculatePayments($invoice);
            }
        });
    }
}
