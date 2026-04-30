<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PurchaseOrderSeeder extends Seeder
{
    public function run(): void
    {
        $businessId = app(\App\Support\Tenancy\CurrentBusiness::class)->id();
        $now = Carbon::now();
        $inventoryAdminId = 4; // Suresh - Inventory

        $products = DB::table('products')
            ->where('business_id', $businessId)
            ->select('id', 'name', 'hsn_code', 'unit', 'purchase_price', 'tax_percent')
            ->get()
            ->keyBy('id');

        // Position-based ID maps (translate hardcoded 1-based ids to per-business ids)
        $vendorIdByPosition = DB::table('vendors')
            ->where('business_id', $businessId)
            ->orderBy('id')
            ->pluck('id')
            ->values()
            ->all();

        $productIdByPosition = DB::table('products')
            ->where('business_id', $businessId)
            ->orderBy('id')
            ->pluck('id')
            ->values()
            ->all();

        // PO definitions: vendor_id, status, product_ids with quantities
        $pos = [
            // 1 draft
            ['num' => 'PO-2026-0001', 'vendor_id' => 1,  'status' => 'draft',    'days_ago' => 5,   'expected_days' => 15, 'items' => [[26, 100], [27, 80]]],
            // 1 sent
            ['num' => 'PO-2026-0002', 'vendor_id' => 4,  'status' => 'sent',     'days_ago' => 15,  'expected_days' => 20, 'items' => [[28, 200], [29, 150]]],
            // 2 partial
            ['num' => 'PO-2026-0003', 'vendor_id' => 1,  'status' => 'partial',  'days_ago' => 45,  'expected_days' => 15, 'items' => [[26, 200], [27, 150], [30, 100]]],
            ['num' => 'PO-2026-0004', 'vendor_id' => 8,  'status' => 'partial',  'days_ago' => 35,  'expected_days' => 15, 'items' => [[31, 500], [32, 1000]]],
            // 2 received
            ['num' => 'PO-2026-0005', 'vendor_id' => 3,  'status' => 'received', 'days_ago' => 90,  'expected_days' => 15, 'items' => [[26, 300], [33, 20]]],
            ['num' => 'PO-2026-0006', 'vendor_id' => 10, 'status' => 'received', 'days_ago' => 70,  'expected_days' => 15, 'items' => [[38, 500], [39, 1000], [40, 200]]],
        ];

        foreach ($pos as $po) {
            $poDate = $now->copy()->subDays($po['days_ago']);
            $expectedDate = $poDate->copy()->addDays($po['expected_days']);

            // Translate hardcoded vendor id; fall back to first vendor if out of range
            $resolvedVendorId = $vendorIdByPosition[$po['vendor_id'] - 1] ?? $vendorIdByPosition[0];

            $poId = DB::table('purchase_orders')->insertGetId([
                'business_id' => $businessId,
                'po_number' => $po['num'],
                'vendor_id' => $resolvedVendorId,
                'po_date' => $poDate->toDateString(),
                'expected_date' => $expectedDate->toDateString(),
                'status' => $po['status'],
                'subtotal' => 0,
                'discount_type' => 'percent',
                'discount_value' => 0,
                'tax_percent' => 0,
                'tax_amount' => 0,
                'grand_total' => 0,
                'terms' => 'Standard purchase terms apply.',
                'notes' => null,
                'created_by' => $inventoryAdminId,
                'updated_by' => null,
                'deleted_by' => null,
                'created_at' => $poDate,
                'updated_at' => $poDate,
                'deleted_at' => null,
            ]);

            $subtotal = 0;
            $totalTax = 0;
            $poItemIds = [];

            foreach ($po['items'] as $sortOrder => [$pid, $qty]) {
                // Translate hardcoded product id via position map; fall back to first product if out of range
                $resolvedProductId = $productIdByPosition[$pid - 1] ?? $productIdByPosition[0];
                $p = $products[$resolvedProductId];
                $rate = $p->purchase_price;
                $lineAmount = $qty * $rate;
                $lineTax = round($lineAmount * $p->tax_percent / 100, 2);
                $lineTotal = round($lineAmount + $lineTax, 2);
                $subtotal += $lineAmount;
                $totalTax += $lineTax;

                $poItemId = DB::table('purchase_order_items')->insertGetId([
                    'business_id' => $businessId,
                    'purchase_order_id' => $poId,
                    'product_id' => $resolvedProductId,
                    'description' => $p->name,
                    'hsn_code' => $p->hsn_code,
                    'quantity' => $qty,
                    'unit' => $p->unit,
                    'rate' => $rate,
                    'discount_percent' => 0,
                    'tax_percent' => $p->tax_percent,
                    'line_total' => $lineTotal,
                    'sort_order' => $sortOrder + 1,
                    'created_at' => $poDate,
                    'updated_at' => $poDate,
                ]);

                $poItemIds[] = ['po_item_id' => $poItemId, 'product_id' => $resolvedProductId, 'qty_ordered' => $qty];
            }

            $grandTotal = round($subtotal + $totalTax, 2);
            DB::table('purchase_orders')->where('id', $poId)->update([
                'subtotal' => round($subtotal, 2),
                'tax_amount' => round($totalTax, 2),
                'grand_total' => $grandTotal,
            ]);

            // Create GoodsReceipt records for partial and received POs
            if (in_array($po['status'], ['partial', 'received'])) {
                $grnDate = $poDate->copy()->addDays($po['expected_days']);

                $grnId = DB::table('goods_receipts')->insertGetId([
                    'business_id' => $businessId,
                    'grn_number' => 'GRN-'.substr($po['num'], 3),
                    'purchase_order_id' => $poId,
                    'received_date' => $grnDate->toDateString(),
                    'notes' => $po['status'] === 'received' ? 'Full quantity received.' : 'Partial delivery received.',
                    'created_by' => $inventoryAdminId,
                    'updated_by' => null,
                    'deleted_by' => null,
                    'created_at' => $grnDate,
                    'updated_at' => $grnDate,
                    'deleted_at' => null,
                ]);

                foreach ($poItemIds as $item) {
                    $qtyReceived = $po['status'] === 'received'
                        ? $item['qty_ordered']
                        : round($item['qty_ordered'] * 0.6); // 60% received for partial

                    DB::table('goods_receipt_items')->insert([
                        'business_id' => $businessId,
                        'goods_receipt_id' => $grnId,
                        'purchase_order_item_id' => $item['po_item_id'],
                        'product_id' => $item['product_id'],
                        'quantity_received' => $qtyReceived,
                        'created_at' => $grnDate,
                        'updated_at' => $grnDate,
                    ]);
                }
            }
        }
    }
}
