<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SalesOrderSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $salesAdminId = 3; // Priya - Sales

        // Accepted quotations are IDs 8-11 (QUO-2026-0008 to QUO-2026-0011)
        // We'll link 8 SOs to quotations, 2 without quotation
        $orders = [
            // Linked to accepted quotations
            ['num' => 'SO-2026-0001', 'quotation_id' => 8,  'status' => 'delivered',  'days_ago' => 80],
            ['num' => 'SO-2026-0002', 'quotation_id' => 9,  'status' => 'shipped',    'days_ago' => 65],
            ['num' => 'SO-2026-0003', 'quotation_id' => 10, 'status' => 'shipped',    'days_ago' => 50],
            ['num' => 'SO-2026-0004', 'quotation_id' => 11, 'status' => 'processing', 'days_ago' => 35],
            // Not linked to quotations - direct orders
            ['num' => 'SO-2026-0005', 'quotation_id' => null, 'customer_id' => 17, 'status' => 'processing', 'days_ago' => 25],
            ['num' => 'SO-2026-0006', 'quotation_id' => null, 'customer_id' => 21, 'status' => 'confirmed',  'days_ago' => 18],
            ['num' => 'SO-2026-0007', 'quotation_id' => null, 'customer_id' => 25, 'status' => 'confirmed',  'days_ago' => 12],
            ['num' => 'SO-2026-0008', 'quotation_id' => null, 'customer_id' => 2,  'status' => 'pending',    'days_ago' => 8],
            ['num' => 'SO-2026-0009', 'quotation_id' => null, 'customer_id' => 6,  'status' => 'pending',    'days_ago' => 4],
            ['num' => 'SO-2026-0010', 'quotation_id' => null, 'customer_id' => 19, 'status' => 'cancelled',  'days_ago' => 30],
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
            ->select('id', 'name', 'hsn_code', 'unit', 'selling_price', 'tax_percent')
            ->get()
            ->keyBy('id');

        foreach ($orders as $idx => $o) {
            $orderDate = $now->copy()->subDays($o['days_ago']);

            // Determine customer_id
            if ($o['quotation_id']) {
                $quotation = DB::table('quotations')->find($o['quotation_id']);
                $customerId = $quotation->customer_id;
            } else {
                $customerId = $o['customer_id'];
            }

            $soId = DB::table('sales_orders')->insertGetId([
                'order_number' => $o['num'],
                'quotation_id' => $o['quotation_id'],
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

            if ($o['quotation_id']) {
                // Copy items from quotation
                $qItems = DB::table('quotation_items')
                    ->where('quotation_id', $o['quotation_id'])
                    ->get();

                foreach ($qItems as $qi) {
                    $lineBeforeDisc = $qi->quantity * $qi->rate;
                    $lineAfterDisc = $lineBeforeDisc * (1 - $qi->discount_percent / 100);
                    $lineTax = round($lineAfterDisc * $qi->tax_percent / 100, 2);
                    $lineTotal = round($lineAfterDisc + $lineTax, 2);
                    $subtotal += $lineAfterDisc;
                    $totalTax += $lineTax;

                    DB::table('sales_order_items')->insert([
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
                    $p = $products[$pid];
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
                        'sales_order_id' => $soId,
                        'product_id' => $pid,
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
