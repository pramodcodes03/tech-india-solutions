<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StockMovementSeeder extends Seeder
{
    public function run(): void
    {
        $businessId = app(\App\Support\Tenancy\CurrentBusiness::class)->id();
        $now = Carbon::now();
        $inventoryAdminId = 4; // Suresh - Inventory

        // Position-based ID maps (translate hardcoded 1-based ids to per-business ids)
        $warehouseIdByPosition = DB::table('warehouses')
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

        // Fall back to first warehouse if a position is out of range
        $mainWarehouseId = $warehouseIdByPosition[0] ?? null; // WH-001
        $ambatturId = $warehouseIdByPosition[1] ?? $mainWarehouseId; // WH-002
        $bangaloreId = $warehouseIdByPosition[2] ?? $mainWarehouseId; // WH-003

        $movements = [];

        // ── Stock-In from GRN receipts (purchase order receipts) ──────────
        $grnItems = DB::table('goods_receipt_items as gri')
            ->join('goods_receipts as gr', 'gri.goods_receipt_id', '=', 'gr.id')
            ->where('gr.business_id', $businessId)
            ->select('gri.product_id', 'gri.quantity_received', 'gr.received_date', 'gr.id as grn_id')
            ->get();

        foreach ($grnItems as $gri) {
            $movements[] = [
                'business_id' => $businessId,
                'product_id' => $gri->product_id,
                'warehouse_id' => $mainWarehouseId,
                'type' => 'in',
                'quantity' => $gri->quantity_received,
                'reference_type' => 'App\\Models\\GoodsReceipt',
                'reference_id' => $gri->grn_id,
                'notes' => 'Stock received from purchase order.',
                'created_by' => $inventoryAdminId,
                'created_at' => $gri->received_date,
            ];
        }

        // ── Stock-Out from confirmed/shipped/delivered sales orders ───────
        $soStatuses = ['confirmed', 'processing', 'shipped', 'delivered'];
        $salesOrders = DB::table('sales_orders')
            ->where('business_id', $businessId)
            ->whereIn('status', $soStatuses)
            ->get();

        foreach ($salesOrders as $so) {
            $soItems = DB::table('sales_order_items')
                ->where('sales_order_id', $so->id)
                ->get();

            foreach ($soItems as $si) {
                $movements[] = [
                    'business_id' => $businessId,
                    'product_id' => $si->product_id,
                    'warehouse_id' => $mainWarehouseId,
                    'type' => 'out',
                    'quantity' => $si->quantity,
                    'reference_type' => 'App\\Models\\SalesOrder',
                    'reference_id' => $so->id,
                    'notes' => 'Stock dispatched for sales order '.$so->order_number.'.',
                    'created_by' => $inventoryAdminId,
                    'created_at' => $so->order_date,
                ];
            }
        }

        // ── Initial stock-in for finished goods (opening stock) ──────────
        $openingStockProducts = range(1, 25); // Finished goods (products 1-25)
        $openingDate = $now->copy()->subMonths(6)->startOfMonth();

        foreach ($openingStockProducts as $pid) {
            // Translate hardcoded product id via position map; fall back to first product if out of range
            $resolvedProductId = $productIdByPosition[$pid - 1] ?? $productIdByPosition[0];
            $qty = rand(50, 200);
            $movements[] = [
                'business_id' => $businessId,
                'product_id' => $resolvedProductId,
                'warehouse_id' => $mainWarehouseId,
                'type' => 'in',
                'quantity' => $qty,
                'reference_type' => null,
                'reference_id' => null,
                'notes' => 'Opening stock balance.',
                'created_by' => $inventoryAdminId,
                'created_at' => $openingDate->toDateString(),
            ];
        }

        // Some stock at other warehouses
        foreach ([1, 3, 6, 7, 16, 17, 21, 22] as $pid) {
            // Translate hardcoded product id via position map; fall back to first product if out of range
            $resolvedProductId = $productIdByPosition[$pid - 1] ?? $productIdByPosition[0];
            $movements[] = [
                'business_id' => $businessId,
                'product_id' => $resolvedProductId,
                'warehouse_id' => $ambatturId,
                'type' => 'in',
                'quantity' => rand(20, 60),
                'reference_type' => null,
                'reference_id' => null,
                'notes' => 'Opening stock balance - Ambattur.',
                'created_by' => $inventoryAdminId,
                'created_at' => $openingDate->toDateString(),
            ];
        }

        foreach ([1, 8, 16, 22, 24] as $pid) {
            // Translate hardcoded product id via position map; fall back to first product if out of range
            $resolvedProductId = $productIdByPosition[$pid - 1] ?? $productIdByPosition[0];
            $movements[] = [
                'business_id' => $businessId,
                'product_id' => $resolvedProductId,
                'warehouse_id' => $bangaloreId,
                'type' => 'in',
                'quantity' => rand(15, 40),
                'reference_type' => null,
                'reference_id' => null,
                'notes' => 'Opening stock balance - Bangalore.',
                'created_by' => $inventoryAdminId,
                'created_at' => $openingDate->toDateString(),
            ];
        }

        // ── Manual adjustments ───────────────────────────────────────────
        $adjustments = [
            ['product_id' => 3,  'warehouse_id' => $mainWarehouseId, 'quantity' => -5,  'notes' => 'Damaged stock written off - Leather Wallet.',          'days_ago' => 60],
            ['product_id' => 11, 'warehouse_id' => $mainWarehouseId, 'quantity' => -8,  'notes' => 'Stock count adjustment - Leather Keychain shortage.',   'days_ago' => 45],
            ['product_id' => 6,  'warehouse_id' => $mainWarehouseId, 'quantity' => -3,  'notes' => 'Quality reject - Cotton Polo T-Shirt defective batch.', 'days_ago' => 30],
            ['product_id' => 38, 'warehouse_id' => $mainWarehouseId, 'quantity' => 20,  'notes' => 'Stock count correction - Cardboard Gift Box surplus.',  'days_ago' => 20],
            ['product_id' => 26, 'warehouse_id' => $mainWarehouseId, 'quantity' => -10, 'notes' => 'Wastage adjustment - Leather Hide trim waste.',        'days_ago' => 15],
            ['product_id' => 30, 'warehouse_id' => $ambatturId,      'quantity' => 50,  'notes' => 'Inter-warehouse transfer from Main.',                  'days_ago' => 10],
        ];

        foreach ($adjustments as $adj) {
            $adjDate = $now->copy()->subDays($adj['days_ago']);
            // Translate hardcoded product id via position map; fall back to first product if out of range
            $resolvedProductId = $productIdByPosition[$adj['product_id'] - 1] ?? $productIdByPosition[0];
            $movements[] = [
                'business_id' => $businessId,
                'product_id' => $resolvedProductId,
                'warehouse_id' => $adj['warehouse_id'],
                'type' => 'adjustment',
                'quantity' => $adj['quantity'],
                'reference_type' => null,
                'reference_id' => null,
                'notes' => $adj['notes'],
                'created_by' => $inventoryAdminId,
                'created_at' => $adjDate->toDateString(),
            ];
        }

        // Insert all movements
        DB::table('stock_movements')->insert($movements);
    }
}
