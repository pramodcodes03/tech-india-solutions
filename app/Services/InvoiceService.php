<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    /**
     * Generate the next invoice number in INV-YYYY-0001 format.
     */
    public function generateNumber(): string
    {
        $year = date('Y');
        $prefix = "INV-{$year}-";
        $last = Invoice::withTrashed()
            ->where('invoice_number', 'like', $prefix.'%')
            ->orderByDesc('invoice_number')
            ->first();

        $nextNumber = $last ? (int) substr($last->invoice_number, strlen($prefix)) + 1 : 1;

        return $prefix.str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create an invoice with its line items.
     */
    public function create(array $data, array $items): Invoice
    {
        return DB::transaction(function () use ($data, $items) {
            $data['invoice_number'] = $this->generateNumber();
            $data['created_by'] = Auth::guard('admin')->id();

            $items = $this->normalizeItems($items);
            $totals = $this->calculateTotals(
                $items,
                $data['discount_type'] ?? 'percent',
                (float) ($data['discount_value'] ?? 0),
                (float) ($data['tax_percent'] ?? 0),
            );

            $data = array_merge($data, $totals);
            $data['amount_paid'] = $data['amount_paid'] ?? 0;
            $data['balance_due'] = $data['balance_due'] ?? $totals['grand_total'];
            $data['status'] = $data['status'] ?? 'unpaid';

            $invoice = Invoice::create($data);

            foreach ($items as $index => $item) {
                $item['invoice_id'] = $invoice->id;
                $item['sort_order'] = $item['sort_order'] ?? $index + 1;
                InvoiceItem::create($item);
            }

            return $invoice->load('items');
        });
    }

    /**
     * Update an invoice and its items.
     */
    public function update(Invoice $invoice, array $data, array $items): Invoice
    {
        return DB::transaction(function () use ($invoice, $data, $items) {
            $data['updated_by'] = Auth::guard('admin')->id();

            $items = $this->normalizeItems($items);
            $totals = $this->calculateTotals(
                $items,
                $data['discount_type'] ?? $invoice->discount_type ?? 'percent',
                (float) ($data['discount_value'] ?? $invoice->discount_value ?? 0),
                (float) ($data['tax_percent'] ?? $invoice->tax_percent ?? 0),
            );

            $data = array_merge($data, $totals);

            $invoice->update($data);

            // Delete old items and create new ones
            $invoice->items()->delete();

            foreach ($items as $index => $item) {
                $item['invoice_id'] = $invoice->id;
                $item['sort_order'] = $item['sort_order'] ?? $index + 1;
                InvoiceItem::create($item);
            }

            // Recalculate payment totals after update
            $this->recalculatePayments($invoice->refresh());

            return $invoice->refresh()->load('items');
        });
    }

    /**
     * Soft-delete an invoice.
     */
    public function delete(Invoice $invoice): void
    {
        $invoice->update(['deleted_by' => Auth::guard('admin')->id()]);
        $invoice->delete();
    }

    /**
     * Recalculate payment totals for an invoice.
     *
     * Sums all payments, updates amount_paid, balance_due, and status.
     */
    public function recalculatePayments(Invoice $invoice): void
    {
        $totalPaid = $invoice->payments()->sum('amount');
        $balanceDue = (float) $invoice->grand_total - (float) $totalPaid;

        if ($balanceDue <= 0) {
            $status = 'paid';
            $balanceDue = 0;
        } elseif ($totalPaid > 0) {
            $status = 'partial';
        } else {
            $status = 'unpaid';
        }

        $invoice->update([
            'amount_paid' => round($totalPaid, 2),
            'balance_due' => round($balanceDue, 2),
            'status' => $status,
        ]);
    }

    /**
     * Compute line_total per item: qty × rate − line discount %.
     * Mutates and returns the items array so callers store correct totals.
     */
    private function normalizeItems(array $items): array
    {
        foreach ($items as $i => $item) {
            $qty = (float) ($item['quantity'] ?? 0);
            $rate = (float) ($item['rate'] ?? 0);
            $lineDiscPct = (float) ($item['discount_percent'] ?? 0);
            $gross = $qty * $rate;
            $lineDisc = $gross * ($lineDiscPct / 100);
            $items[$i]['line_total'] = round($gross - $lineDisc, 2);
        }

        return $items;
    }

    /**
     * Calculate subtotal, tax_amount, and grand_total from line items.
     *
     * subtotal     = sum of (qty × rate − line discount %) per item
     * discount     = invoice-level discount on subtotal (percent or fixed)
     * tax_amount   = sum of per-line tax%, computed on each line's
     *                share after invoice-level discount distributed pro-rata
     * grand_total  = subtotal − discount + tax
     */
    private function calculateTotals(array $items, string $discountType, float $discountValue, float $taxPercent): array
    {
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += (float) ($item['line_total'] ?? 0);
        }

        $discountAmount = $discountType === 'percent'
            ? $subtotal * ($discountValue / 100)
            : $discountValue;
        $discountAmount = min($discountAmount, $subtotal);

        $afterDiscount = $subtotal - $discountAmount;

        // Tax: prefer per-line tax% (more accurate when lines have different rates).
        // Fall back to invoice-level tax_percent if no line-level tax is set.
        $taxAmount = 0;
        $hasLineTax = false;
        if ($subtotal > 0) {
            foreach ($items as $item) {
                $lineTax = (float) ($item['tax_percent'] ?? 0);
                if ($lineTax > 0) {
                    $hasLineTax = true;
                    $linePortion = ((float) $item['line_total'] / $subtotal) * $afterDiscount;
                    $taxAmount += $linePortion * ($lineTax / 100);
                }
            }
        }
        if (! $hasLineTax) {
            $taxAmount = $afterDiscount * ($taxPercent / 100);
        }

        $grandTotal = $afterDiscount + $taxAmount;

        return [
            'subtotal' => round($subtotal, 2),
            'tax_amount' => round($taxAmount, 2),
            'grand_total' => round($grandTotal, 2),
        ];
    }
}
