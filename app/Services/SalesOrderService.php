<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SalesOrderService
{
    public function __construct(
        protected InventoryService $inventoryService,
        protected InvoiceService $invoiceService,
    ) {}

    /**
     * Generate the next sales order number in SO-YYYY-0001 format.
     */
    public function generateNumber(): string
    {
        $year = date('Y');
        $prefix = "SO-{$year}-";
        $last = SalesOrder::withTrashed()
            ->where('order_number', 'like', $prefix.'%')
            ->orderByDesc('order_number')
            ->first();

        $nextNumber = $last ? (int) substr($last->order_number, strlen($prefix)) + 1 : 1;

        return $prefix.str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create a sales order with its line items.
     */
    public function create(array $data, array $items): SalesOrder
    {
        return DB::transaction(function () use ($data, $items) {
            $data['order_number'] = $this->generateNumber();
            $data['created_by'] = Auth::guard('admin')->id();

            $totals = $this->calculateTotals(
                $items,
                $data['discount_type'] ?? 'fixed',
                (float) ($data['discount_value'] ?? 0),
                (float) ($data['tax_percent'] ?? 0),
            );

            $data = array_merge($data, $totals);

            $order = SalesOrder::create($data);

            foreach ($items as $index => $item) {
                $item['sales_order_id'] = $order->id;
                $item['sort_order'] = $item['sort_order'] ?? $index + 1;
                SalesOrderItem::create($item);
            }

            return $order->load('items');
        });
    }

    /**
     * Update a sales order (cannot update if delivered or cancelled).
     */
    public function update(SalesOrder $order, array $data, array $items): SalesOrder
    {
        if (in_array($order->status, ['delivered', 'cancelled'])) {
            throw new \RuntimeException("Cannot update a sales order with status '{$order->status}'.");
        }

        return DB::transaction(function () use ($order, $data, $items) {
            $data['updated_by'] = Auth::guard('admin')->id();

            $totals = $this->calculateTotals(
                $items,
                $data['discount_type'] ?? $order->discount_type ?? 'fixed',
                (float) ($data['discount_value'] ?? $order->discount_value ?? 0),
                (float) ($data['tax_percent'] ?? $order->tax_percent ?? 0),
            );

            $data = array_merge($data, $totals);

            $order->update($data);

            // Delete old items and create new ones
            $order->items()->delete();

            foreach ($items as $index => $item) {
                $item['sales_order_id'] = $order->id;
                $item['sort_order'] = $item['sort_order'] ?? $index + 1;
                SalesOrderItem::create($item);
            }

            return $order->refresh()->load('items');
        });
    }

    /**
     * Soft-delete a sales order.
     */
    public function delete(SalesOrder $order): void
    {
        $order->update(['deleted_by' => Auth::guard('admin')->id()]);
        $order->delete();
    }

    /**
     * Update the status of a sales order with transition validation.
     *
     * When status moves to "confirmed", stock is decremented via InventoryService.
     */
    public function updateStatus(SalesOrder $order, string $status): SalesOrder
    {
        $allowedTransitions = [
            'pending' => ['confirmed', 'cancelled'],
            'confirmed' => ['processing', 'cancelled'],
            'processing' => ['shipped', 'cancelled'],
            'shipped' => ['delivered'],
            'delivered' => [],
            'cancelled' => [],
        ];

        $currentStatus = $order->status;

        if (! isset($allowedTransitions[$currentStatus]) || ! in_array($status, $allowedTransitions[$currentStatus])) {
            throw new \RuntimeException("Invalid status transition from '{$currentStatus}' to '{$status}'.");
        }

        return DB::transaction(function () use ($order, $status) {
            $adminId = Auth::guard('admin')->id();

            // When confirming, decrement stock for each line item
            if ($status === 'confirmed') {
                $defaultWarehouse = Warehouse::where('is_default', true)->first();
                foreach ($order->items as $item) {
                    if ($item->product_id && $defaultWarehouse) {
                        $this->inventoryService->recordMovement([
                            'product_id' => $item->product_id,
                            'warehouse_id' => $defaultWarehouse->id,
                            'type' => 'out',
                            'quantity' => $item->quantity,
                            'reference_type' => SalesOrder::class,
                            'reference_id' => $order->id,
                            'notes' => "Stock out for Sales Order #{$order->order_number}",
                        ]);
                    }
                }
            }

            $order->update([
                'status' => $status,
                'updated_by' => $adminId,
            ]);

            return $order->refresh();
        });
    }

    /**
     * Generate an invoice from a sales order.
     */
    public function generateInvoice(SalesOrder $order): Invoice
    {
        return DB::transaction(function () use ($order) {
            $invoiceData = [
                'customer_id' => $order->customer_id,
                'sales_order_id' => $order->id,
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'discount_type' => $order->discount_type,
                'discount_value' => $order->discount_value,
                'tax_percent' => $order->tax_percent,
                'terms' => $order->terms,
                'notes' => $order->notes,
                'status' => 'unpaid',
            ];

            $invoiceItems = $order->items->map(function ($item) {
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

            return $this->invoiceService->create($invoiceData, $invoiceItems);
        });
    }

    /**
     * Calculate subtotal, tax_amount, and grand_total from line items.
     */
    private function calculateTotals(array $items, string $discountType, float $discountValue, float $taxPercent): array
    {
        $subtotal = 0;
        foreach ($items as $item) {
            $lineTotal = (float) ($item['line_total'] ?? ((float) ($item['quantity'] ?? 0) * (float) ($item['rate'] ?? 0)));
            $subtotal += $lineTotal;
        }

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
}
