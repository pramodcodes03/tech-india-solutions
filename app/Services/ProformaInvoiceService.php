<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\ProformaInvoice;
use App\Models\ProformaInvoiceItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProformaInvoiceService
{
    public function __construct(
        protected InvoiceService $invoiceService,
    ) {}

    /**
     * Generate the next proforma invoice number in PI-YYYY-0001 format.
     */
    public function generateNumber(): string
    {
        $year = date('Y');
        $prefix = "PI-{$year}-";
        $last = ProformaInvoice::withTrashed()
            ->where('proforma_number', 'like', $prefix.'%')
            ->orderByDesc('proforma_number')
            ->first();

        $nextNumber = $last ? (int) substr($last->proforma_number, strlen($prefix)) + 1 : 1;

        return $prefix.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }

    public function create(array $data, array $items): ProformaInvoice
    {
        return DB::transaction(function () use ($data, $items) {
            $data['proforma_number'] = $this->generateNumber();
            $data['created_by'] = Auth::guard('admin')->id();

            $totals = $this->calculateTotals(
                $items,
                $data['discount_type'] ?? 'percent',
                (float) ($data['discount_value'] ?? 0),
                (float) ($data['tax_percent'] ?? 0),
            );

            $data = array_merge($data, $totals);

            $proforma = ProformaInvoice::create($data);

            foreach ($items as $index => $item) {
                $item['proforma_invoice_id'] = $proforma->id;
                $item['sort_order'] = $item['sort_order'] ?? $index + 1;
                ProformaInvoiceItem::create($item);
            }

            return $proforma->load('items');
        });
    }

    public function update(ProformaInvoice $proforma, array $data, array $items): ProformaInvoice
    {
        return DB::transaction(function () use ($proforma, $data, $items) {
            $data['updated_by'] = Auth::guard('admin')->id();

            $totals = $this->calculateTotals(
                $items,
                $data['discount_type'] ?? $proforma->discount_type ?? 'percent',
                (float) ($data['discount_value'] ?? $proforma->discount_value ?? 0),
                (float) ($data['tax_percent'] ?? $proforma->tax_percent ?? 0),
            );

            $data = array_merge($data, $totals);

            $proforma->update($data);
            $proforma->items()->delete();

            foreach ($items as $index => $item) {
                $item['proforma_invoice_id'] = $proforma->id;
                $item['sort_order'] = $item['sort_order'] ?? $index + 1;
                ProformaInvoiceItem::create($item);
            }

            return $proforma->refresh()->load('items');
        });
    }

    public function delete(ProformaInvoice $proforma): void
    {
        $proforma->update(['deleted_by' => Auth::guard('admin')->id()]);
        $proforma->delete();
    }

    public function calculateTotals(array $items, string $discountType, float $discountValue, float $taxPercent): array
    {
        $subtotal = 0;
        foreach ($items as $item) {
            $lineTotal = (float) ($item['line_total'] ?? ((float) ($item['quantity'] ?? 0) * (float) ($item['rate'] ?? 0)));
            $subtotal += $lineTotal;
        }

        $discountAmount = $discountType === 'percent'
            ? $subtotal * ($discountValue / 100)
            : $discountValue;

        $afterDiscount = $subtotal - $discountAmount;
        $taxAmount = $afterDiscount * ($taxPercent / 100);
        $grandTotal = $afterDiscount + $taxAmount;

        return [
            'subtotal' => round($subtotal, 2),
            'tax_amount' => round($taxAmount, 2),
            'grand_total' => round($grandTotal, 2),
        ];
    }

    public function clone(ProformaInvoice $proforma): ProformaInvoice
    {
        return DB::transaction(function () use ($proforma) {
            $clone = $proforma->replicate(['id', 'proforma_number', 'status', 'invoice_id', 'created_at', 'updated_at', 'deleted_at']);
            $clone->proforma_number = $this->generateNumber();
            $clone->status = 'draft';
            $clone->invoice_id = null;
            $clone->created_by = Auth::guard('admin')->id();
            $clone->updated_by = null;
            $clone->deleted_by = null;
            $clone->save();

            foreach ($proforma->items as $item) {
                $newItem = $item->replicate(['id', 'proforma_invoice_id', 'created_at', 'updated_at']);
                $newItem->proforma_invoice_id = $clone->id;
                $newItem->save();
            }

            return $clone->load('items');
        });
    }

    /**
     * Convert the proforma into a tax invoice.
     *
     * Copies header + items to a new Invoice, links both records, marks proforma as "converted".
     */
    public function convertToInvoice(ProformaInvoice $proforma): Invoice
    {
        return DB::transaction(function () use ($proforma) {
            $adminId = Auth::guard('admin')->id();

            $invoiceData = [
                'customer_id' => $proforma->customer_id,
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(15)->toDateString(),
                'discount_type' => $proforma->discount_type,
                'discount_value' => $proforma->discount_value,
                'tax_percent' => $proforma->tax_percent,
                'terms' => $proforma->terms,
                'notes' => $proforma->notes,
                'amount_paid' => (float) $proforma->advance_received,
            ];

            $invoiceItems = $proforma->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'description' => $item->description,
                'hsn_code' => $item->hsn_code,
                'quantity' => $item->quantity,
                'unit' => $item->unit,
                'rate' => $item->rate,
                'discount_percent' => $item->discount_percent,
                'tax_percent' => $item->tax_percent,
                'line_total' => $item->line_total,
                'sort_order' => $item->sort_order,
            ])->toArray();

            $invoice = $this->invoiceService->create($invoiceData, $invoiceItems);

            $proforma->update([
                'status' => 'converted',
                'invoice_id' => $invoice->id,
                'updated_by' => $adminId,
            ]);

            return $invoice;
        });
    }
}
