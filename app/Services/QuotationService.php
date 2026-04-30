<?php

namespace App\Services;

use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\SalesOrder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QuotationService
{
    public function __construct(
        protected SalesOrderService $salesOrderService,
    ) {}

    /**
     * Generate the next quotation number in QUO-YYYY-0001 format (year-based reset).
     */
    public function generateNumber(): string
    {
        $year = date('Y');
        $base = app(\App\Support\Tenancy\CurrentBusiness::class)->get()?->quotation_prefix ?? 'QUO-';
        $prefix = $base.$year.'-';
        $last = Quotation::withTrashed()
            ->where('quotation_number', 'like', $prefix.'%')
            ->orderByDesc('quotation_number')
            ->first();

        $nextNumber = $last ? (int) substr($last->quotation_number, strlen($prefix)) + 1 : 1;

        return $prefix.str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create a quotation with its line items inside a transaction.
     */
    public function create(array $data, array $items): Quotation
    {
        return DB::transaction(function () use ($data, $items) {
            $data['quotation_number'] = $this->generateNumber();
            $data['created_by'] = Auth::guard('admin')->id();

            $totals = $this->calculateTotals(
                $items,
                $data['discount_type'] ?? 'fixed',
                (float) ($data['discount_value'] ?? 0),
                (float) ($data['tax_percent'] ?? 0),
            );

            $data = array_merge($data, $totals);

            $quotation = Quotation::create($data);

            foreach ($items as $index => $item) {
                $item['quotation_id'] = $quotation->id;
                $item['sort_order'] = $item['sort_order'] ?? $index + 1;
                QuotationItem::create($item);
            }

            return $quotation->load('items');
        });
    }

    /**
     * Update a quotation: replace header data and recreate items.
     */
    public function update(Quotation $quotation, array $data, array $items): Quotation
    {
        return DB::transaction(function () use ($quotation, $data, $items) {
            $data['updated_by'] = Auth::guard('admin')->id();

            $totals = $this->calculateTotals(
                $items,
                $data['discount_type'] ?? $quotation->discount_type ?? 'fixed',
                (float) ($data['discount_value'] ?? $quotation->discount_value ?? 0),
                (float) ($data['tax_percent'] ?? $quotation->tax_percent ?? 0),
            );

            $data = array_merge($data, $totals);

            $quotation->update($data);

            // Delete old items and create new ones
            $quotation->items()->delete();

            foreach ($items as $index => $item) {
                $item['quotation_id'] = $quotation->id;
                $item['sort_order'] = $item['sort_order'] ?? $index + 1;
                QuotationItem::create($item);
            }

            return $quotation->refresh()->load('items');
        });
    }

    /**
     * Soft-delete a quotation.
     */
    public function delete(Quotation $quotation): void
    {
        $quotation->update(['deleted_by' => Auth::guard('admin')->id()]);
        $quotation->delete();
    }

    /**
     * Calculate subtotal, tax_amount, and grand_total from line items.
     */
    public function calculateTotals(array $items, string $discountType, float $discountValue, float $taxPercent): array
    {
        $subtotal = 0;
        foreach ($items as $item) {
            $lineTotal = (float) ($item['line_total'] ?? ((float) ($item['quantity'] ?? 0) * (float) ($item['rate'] ?? 0)));
            $subtotal += $lineTotal;
        }

        // Apply discount
        $discountAmount = 0;
        if ($discountType === 'percentage') {
            $discountAmount = $subtotal * ($discountValue / 100);
        } else {
            $discountAmount = $discountValue;
        }

        $afterDiscount = $subtotal - $discountAmount;
        $taxAmount = $afterDiscount * ($taxPercent / 100);
        $grandTotal = $afterDiscount + $taxAmount;

        return [
            'subtotal' => round($subtotal, 2),
            'tax_amount' => round($taxAmount, 2),
            'grand_total' => round($grandTotal, 2),
        ];
    }

    /**
     * Clone a quotation with a new number and draft status.
     */
    public function clone(Quotation $quotation): Quotation
    {
        return DB::transaction(function () use ($quotation) {
            $newQuotation = $quotation->replicate(['id', 'quotation_number', 'status', 'created_at', 'updated_at', 'deleted_at']);
            $newQuotation->quotation_number = $this->generateNumber();
            $newQuotation->status = 'draft';
            $newQuotation->created_by = Auth::guard('admin')->id();
            $newQuotation->updated_by = null;
            $newQuotation->deleted_by = null;
            $newQuotation->save();

            foreach ($quotation->items as $item) {
                $newItem = $item->replicate(['id', 'quotation_id', 'created_at', 'updated_at']);
                $newItem->quotation_id = $newQuotation->id;
                $newItem->save();
            }

            return $newQuotation->load('items');
        });
    }

    /**
     * Convert a quotation to a sales order.
     *
     * Copies all data to a new SalesOrder, marks the quotation as accepted.
     */
    public function convertToSalesOrder(Quotation $quotation): SalesOrder
    {
        return DB::transaction(function () use ($quotation) {
            $adminId = Auth::guard('admin')->id();

            $orderData = [
                'quotation_id' => $quotation->id,
                'customer_id' => $quotation->customer_id,
                'order_date' => now()->toDateString(),
                'status' => 'draft',
                'discount_type' => $quotation->discount_type,
                'discount_value' => $quotation->discount_value,
                'tax_percent' => $quotation->tax_percent,
                'terms' => $quotation->terms,
                'notes' => $quotation->notes,
            ];

            $orderItems = $quotation->items->map(function ($item) {
                return [
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
                ];
            })->toArray();

            $salesOrder = $this->salesOrderService->create($orderData, $orderItems);

            $quotation->update([
                'status' => 'accepted',
                'updated_by' => $adminId,
            ]);

            return $salesOrder;
        });
    }
}
