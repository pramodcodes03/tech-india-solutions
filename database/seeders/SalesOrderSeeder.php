<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SalesOrderSeeder extends Seeder
{
    public function run(): void
    {
        $businessId = app(\App\Support\Tenancy\CurrentBusiness::class)->id();
        $now = Carbon::now();
        $salesAdminId = 3; // Priya - Sales

        // Accepted quotations are IDs 8-11 (QUO-2026-0008 to QUO-2026-0011)
        // We'll link 8 SOs to quotations, 2 without quotation
        // NOTE: quotation_id values below refer to quotation_numbers (e.g. QUO-2026-0008..0011), resolved per-business below.
        $orders = [
            // Linked to accepted quotations (by quotation_number)
            ['num' => 'SO-2026-0001', 'quotation_number' => 'QUO-2026-0008',  'status' => 'delivered',  'days_ago' => 80],
            ['num' => 'SO-2026-0002', 'quotation_number' => 'QUO-2026-0009',  'status' => 'shipped',    'days_ago' => 65],
            ['num' => 'SO-2026-0003', 'quotation_number' => 'QUO-2026-0010', 'status' => 'shipped',    'days_ago' => 50],
            ['num' => 'SO-2026-0004', 'quotation_number' => 'QUO-2026-0011', 'status' => 'processing', 'days_ago' => 35],
            // Not linked to quotations - direct orders
            ['num' => 'SO-2026-0005', 'quotation_number' => null, 'customer_id' => 17, 'status' => 'processing', 'days_ago' => 25],
            ['num' => 'SO-2026-0006', 'quotation_number' => null, 'customer_id' => 21, 'status' => 'confirmed',  'days_ago' => 18],
            ['num' => 'SO-2026-0007', 'quotation_number' => null, 'customer_id' => 25, 'status' => 'confirmed',  'days_ago' => 12],
            ['num' => 'SO-2026-0008', 'quotation_number' => null, 'customer_id' => 2,  'status' => 'pending',    'days_ago' => 8],
            ['num' => 'SO-2026-0009', 'quotation_number' => null, 'customer_id' => 6,  'status' => 'pending',    'days_ago' => 4],
            ['num' => 'SO-2026-0010', 'quotation_number' => null, 'customer_id' => 19, 'status' => 'cancelled',  'days_ago' => 30],
        ];

        // Product sets for non-quotation orders (keyed by order number)
        $directProductSets = [
            'SO-2026-0005' => [1, 3, 11, 21],
            'SO-2026-0006' => [6, 7, 8],
            'SO-2026-0007' => [16, 17, 22],
            'SO-2026-0008' => [1, 2, 4, 5],
            'SO-2026-0009' => [21, 24, 25],
            'SO-2026-0010' => [8, 9, 12],
        ];

        $products = DB::table('products')
            ->where('business_id', $businessId)
            ->select('id', 'name', 'hsn_code', 'unit', 'selling_price', 'tax_percent')
            ->get()
            ->keyBy('id');

        // Position-based ID maps (translate hardcoded 1-based ids to per-business ids)
        $customerIdByPosition = DB::table('customers')
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

        foreach ($orders as $idx => $o) {
            $orderDate = $now->copy()->subDays($o['days_ago']);

            // Determine customer_id and resolved quotation_id (per-business by quotation_number)
            $resolvedQuotationId = null;
            if ($o['quotation_number']) {
                $quotation = DB::table('quotations')
                    ->where('business_id', $businessId)
                    ->where('quotation_number', $o['quotation_number'])
                    ->first();
                $resolvedQuotationId = $quotation->id;
                $customerId = $quotation->customer_id;
            } else {
                // Translate hardcoded customer id; fall back to first customer if out of range
                $custIdx = $o['customer_id'] - 1;
                $customerId = $customerIdByPosition[$custIdx] ?? $customerIdByPosition[0];
            }

            $soId = DB::table('sales_orders')->insertGetId([
                'business_id' => $businessId,
                'order_number' => $o['num'],
                'quotation_id' => $resolvedQuotationId,
                'customer_id' => $customerId,
                'order_date' => $orderDate->toDateString(),
                'status' => $o['status'],
                'subtotal' => 0,
                'discount_type' => 'percent',
                'discount_value' => 0,
                'tax_percent' => 0,
                'tax_amount' => 0,
                'grand_total' => 0,
                'terms' => 'Standard terms and conditions apply.',
                'notes' => null,
                'created_by' => $salesAdminId,
                'updated_by' => null,
                'deleted_by' => null,
                'created_at' => $orderDate,
                'updated_at' => $orderDate,
                'deleted_at' => null,
            ]);

            $subtotal = 0;
            $totalTax = 0;

            if ($resolvedQuotationId) {
                // Copy items from quotation (per-business resolved id)
                $qItems = DB::table('quotation_items')
                    ->where('business_id', $businessId)
                    ->where('quotation_id', $resolvedQuotationId)
                    ->get();

                foreach ($qItems as $qi) {
                    $lineBeforeDisc = $qi->quantity * $qi->rate;
                    $lineAfterDisc = $lineBeforeDisc * (1 - $qi->discount_percent / 100);
                    $lineTax = round($lineAfterDisc * $qi->tax_percent / 100, 2);
                    $lineTotal = round($lineAfterDisc + $lineTax, 2);
                    $subtotal += $lineAfterDisc;
                    $totalTax += $lineTax;

                    DB::table('sales_order_items')->insert([
                        'business_id' => $businessId,
                        'sales_order_id' => $soId,
                        'product_id' => $qi->product_id,
                        'description' => $qi->description,
                        'hsn_code' => $qi->hsn_code,
                        'quantity' => $qi->quantity,
                        'unit' => $qi->unit,
                        'rate' => $qi->rate,
                        'discount_percent' => $qi->discount_percent,
                        'tax_percent' => $qi->tax_percent,
                        'line_total' => $lineTotal,
                        'sort_order' => $qi->sort_order,
                        'created_at' => $orderDate,
                        'updated_at' => $orderDate,
                    ]);
                }
            } else {
                // Create items for direct orders
                $pids = $directProductSets[$o['num']];
                foreach ($pids as $sortOrder => $pid) {
                    // Translate hardcoded product id via position map; fall back to first product if out of range
                    $resolvedProductId = $productIdByPosition[$pid - 1] ?? $productIdByPosition[0];
                    $p = $products[$resolvedProductId];
                    $qty = rand(3, 15);
                    $rate = $p->selling_price;
                    $discPct = 0;
                    $lineBeforeDisc = $qty * $rate;
                    $lineAfterDisc = $lineBeforeDisc;
                    $lineTax = round($lineAfterDisc * $p->tax_percent / 100, 2);
                    $lineTotal = round($lineAfterDisc + $lineTax, 2);
                    $subtotal += $lineAfterDisc;
                    $totalTax += $lineTax;

                    DB::table('sales_order_items')->insert([
                        'business_id' => $businessId,
                        'sales_order_id' => $soId,
                        'product_id' => $resolvedProductId,
                        'description' => $p->name,
                        'hsn_code' => $p->hsn_code,
                        'quantity' => $qty,
                        'unit' => $p->unit,
                        'rate' => $rate,
                        'discount_percent' => $discPct,
                        'tax_percent' => $p->tax_percent,
                        'line_total' => $lineTotal,
                        'sort_order' => $sortOrder + 1,
                        'created_at' => $orderDate,
                        'updated_at' => $orderDate,
                    ]);
                }
            }

            $grandTotal = round($subtotal + $totalTax, 2);

            DB::table('sales_orders')->where('id', $soId)->update([
                'subtotal' => round($subtotal, 2),
                'tax_amount' => round($totalTax, 2),
                'grand_total' => $grandTotal,
            ]);
        }
    }
}
