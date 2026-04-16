<?php

namespace App\Services;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    public function __construct(
        protected InventoryService $inventoryService,
    ) {}

    /**
     * Generate the next purchase order number in PO-YYYY-0001 format.
     */
    public function generateNumber(): string
    {
        $year = date('Y');
        $prefix = "PO-{$year}-";
        $last = PurchaseOrder::withTrashed()
            ->where('po_number', 'like', $prefix.'%')
            ->orderByDesc('po_number')
            ->first();

        $nextNumber = $last ? (int) substr($last->po_number, strlen($prefix)) + 1 : 1;

        return $prefix.str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create a purchase order with its line items.
     */
    public function create(array $data, array $items): PurchaseOrder
    {
        return DB::transaction(function () use ($data, $items) {
            $data['po_number'] = $this->generateNumber();
            $data['created_by'] = Auth::guard('admin')->id();

            $totals = $this->calculateTotals(
                $items,
                $data['discount_type'] ?? 'fixed',
                (float) ($data['discount_value'] ?? 0),
                (float) ($data['tax_percent'] ?? 0),
            );

            $data = array_merge($data, $totals);

            $po = PurchaseOrder::create($data);

            foreach ($items as $index => $item) {
                $item['purchase_order_id'] = $po->id;
                $item['sort_order'] = $item['sort_order'] ?? $index + 1;
                PurchaseOrderItem::create($item);
            }

            return $po->load('items');
        });
    }

    /**
     * Update a purchase order and its items.
     */
    public function update(PurchaseOrder $po, array $data, array $items): PurchaseOrder
    {
        return DB::transaction(function () use ($po, $data, $items) {
            $data['updated_by'] = Auth::guard('admin')->id();

            $totals = $this->calculateTotals(
                $items,
                $data['discount_type'] ?? $po->discount_type ?? 'fixed',
                (float) ($data['discount_value'] ?? $po->discount_value ?? 0),
                (float) ($data['tax_percent'] ?? $po->tax_percent ?? 0),
            );

            $data = array_merge($data, $totals);

            $po->update($data);

            // Delete old items and create new ones
            $po->items()->delete();

            foreach ($items as $index => $item) {
                $item['purchase_order_id'] = $po->id;
                $item['sort_order'] = $item['sort_order'] ?? $index + 1;
                PurchaseOrderItem::create($item);
            }

            return $po->refresh()->load('items');
        });
    }

    /**
     * Soft-delete a purchase order.
     */
    public function delete(PurchaseOrder $po): void
    {
        $po->update(['deleted_by' => Auth::guard('admin')->id()]);
        $po->delete();
    }

    /**
     * Receive goods against a purchase order.
     *
     * Creates a GoodsReceipt with items, records stock_in movements,
     * and updates the PO status (partial or received).
     */
    public function receiveGoods(PurchaseOrder $po, array $items, string $notes): GoodsReceipt
    {
        return DB::transaction(function () use ($po, $items, $notes) {
            $adminId = Auth::guard('admin')->id();

            // Generate GRN number
            $year = date('Y');
            $grnPrefix = "GRN-{$year}-";
            $lastGrn = GoodsReceipt::withTrashed()
                ->where('grn_number', 'like', $grnPrefix.'%')
                ->orderByDesc('grn_number')
                ->first();
            $nextGrn = $lastGrn ? (int) substr($lastGrn->grn_number, strlen($grnPrefix)) + 1 : 1;
            $grnNumber = $grnPrefix.str_pad($nextGrn, 4, '0', STR_PAD_LEFT);

            $grn = GoodsReceipt::create([
                'grn_number' => $grnNumber,
                'purchase_order_id' => $po->id,
                'received_date' => now()->toDateString(),
                'notes' => $notes,
                'created_by' => $adminId,
            ]);

            // Determine a default warehouse for stock movements
            $defaultWarehouseId = $items[0]['warehouse_id'] ?? null;

            foreach ($items as $item) {
                GoodsReceiptItem::create([
                    'goods_receipt_id' => $grn->id,
                    'purchase_order_item_id' => $item['purchase_order_item_id'],
                    'product_id' => $item['product_id'],
                    'quantity_received' => $item['quantity_received'],
                ]);

                // Record stock in movement
                $this->inventoryService->recordMovement([
                    'product_id' => $item['product_id'],
                    'warehouse_id' => $item['warehouse_id'] ?? $defaultWarehouseId,
                    'type' => 'in',
                    'quantity' => $item['quantity_received'],
                    'reference_type' => GoodsReceipt::class,
                    'reference_id' => $grn->id,
                    'notes' => "Goods received for PO #{$po->po_number}",
                    'created_by' => $adminId,
                ]);
            }

            // Determine PO status: check total received vs ordered
            $allReceived = true;
            $anyReceived = false;

            foreach ($po->items as $poItem) {
                $totalReceived = GoodsReceiptItem::where('purchase_order_item_id', $poItem->id)
                    ->sum('quantity_received');

                if ($totalReceived > 0) {
                    $anyReceived = true;
                }

                if ($totalReceived < (float) $poItem->quantity) {
                    $allReceived = false;
                }
            }

            if ($allReceived) {
                $po->update(['status' => 'received', 'updated_by' => $adminId]);
            } elseif ($anyReceived) {
                $po->update(['status' => 'partial', 'updated_by' => $adminId]);
            }

            return $grn->load('items');
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
