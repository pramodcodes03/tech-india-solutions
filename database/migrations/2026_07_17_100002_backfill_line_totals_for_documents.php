<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfill line_total on existing document line items and re-total their parent.
 *
 * The Quotation / Proforma / Sales-Order / Purchase-Order save paths never wrote
 * the per-item line_total, so it stored as 0 (the "Amount" column showed 0.00)
 * and the parent's subtotal / grand_total ignored per-line discount + tax. The
 * services now compute it; this one-off migration repairs the historical rows so
 * they match, using the same formula the app uses:
 *
 *   line_total  = (qty × rate − line discount%) + line tax%
 *   subtotal    = Σ line_total
 *   discount    = header discount (percent or fixed) on subtotal
 *   tax_amount  = (subtotal − discount) × header tax%
 *   grand_total = subtotal − discount + tax_amount
 *
 * Invoices already computed line_total correctly, so they are left untouched.
 */
return new class extends Migration
{
    /** [parent table, item table, foreign key] */
    private array $docs = [
        ['quotations', 'quotation_items', 'quotation_id'],
        ['proforma_invoices', 'proforma_invoice_items', 'proforma_invoice_id'],
        ['sales_orders', 'sales_order_items', 'sales_order_id'],
        ['purchase_orders', 'purchase_order_items', 'purchase_order_id'],
    ];

    public function up(): void
    {
        foreach ($this->docs as [$parentTable, $itemTable, $fk]) {
            // Recompute each item's line_total.
            DB::table($itemTable)->orderBy('id')->each(function ($item) use ($itemTable) {
                $afterDisc = ((float) $item->quantity * (float) $item->rate)
                    * (1 - (float) $item->discount_percent / 100);
                $lineTotal = round($afterDisc * (1 + (float) $item->tax_percent / 100), 2);

                DB::table($itemTable)->where('id', $item->id)->update(['line_total' => $lineTotal]);
            });

            // Re-total each parent from its (now-correct) items.
            DB::table($parentTable)->orderBy('id')->each(function ($parent) use ($parentTable, $itemTable, $fk) {
                $subtotal = (float) DB::table($itemTable)->where($fk, $parent->id)->sum('line_total');

                $discountValue = (float) ($parent->discount_value ?? 0);
                $discountAmount = in_array($parent->discount_type, ['percent', 'percentage'], true)
                    ? $subtotal * ($discountValue / 100)
                    : $discountValue;
                $discountAmount = min($discountAmount, $subtotal);

                $afterDiscount = $subtotal - $discountAmount;
                $taxAmount = $afterDiscount * ((float) ($parent->tax_percent ?? 0) / 100);
                $grandTotal = $afterDiscount + $taxAmount;

                DB::table($parentTable)->where('id', $parent->id)->update([
                    'subtotal' => round($subtotal, 2),
                    'tax_amount' => round($taxAmount, 2),
                    'grand_total' => round($grandTotal, 2),
                ]);
            });
        }
    }

    public function down(): void
    {
        // Data backfill — not reversible.
    }
};
