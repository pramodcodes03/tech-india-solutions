<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuotationSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $salesAdminId = 3; // Priya - Sales

        // Quotation definitions: customer_id, status, days_ago, item count
        $quotations = [
            // 3 draft
            ['num' => 'QUO-2026-0001', 'customer_id' => 1,  'status' => 'draft',    'days_ago' => 5,   'valid_days' => 30],
            ['num' => 'QUO-2026-0002', 'customer_id' => 5,  'status' => 'draft',    'days_ago' => 3,   'valid_days' => 30],
            ['num' => 'QUO-2026-0003', 'customer_id' => 10, 'status' => 'draft',    'days_ago' => 1,   'valid_days' => 30],
            // 4 sent
            ['num' => 'QUO-2026-0004', 'customer_id' => 2,  'status' => 'sent',     'days_ago' => 20,  'valid_days' => 30],
            ['num' => 'QUO-2026-0005', 'customer_id' => 6,  'status' => 'sent',     'days_ago' => 15,  'valid_days' => 30],
            ['num' => 'QUO-2026-0006', 'customer_id' => 8,  'status' => 'sent',     'days_ago' => 12,  'valid_days' => 30],
            ['num' => 'QUO-2026-0007', 'customer_id' => 12, 'status' => 'sent',     'days_ago' => 7,   'valid_days' => 30],
            // 4 accepted
            ['num' => 'QUO-2026-0008', 'customer_id' => 3,  'status' => 'accepted', 'days_ago' => 90,  'valid_days' => 30],
            ['num' => 'QUO-2026-0009', 'customer_id' => 4,  'status' => 'accepted', 'days_ago' => 75,  'valid_days' => 30],
            ['num' => 'QUO-2026-0010', 'customer_id' => 7,  'status' => 'accepted', 'days_ago' => 60,  'valid_days' => 30],
            ['num' => 'QUO-2026-0011', 'customer_id' => 9,  'status' => 'accepted', 'days_ago' => 45,  'valid_days' => 30],
            // 2 rejected
            ['num' => 'QUO-2026-0012', 'customer_id' => 11, 'status' => 'rejected', 'days_ago' => 50,  'valid_days' => 30],
            ['num' => 'QUO-2026-0013', 'customer_id' => 14, 'status' => 'rejected', 'days_ago' => 35,  'valid_days' => 30],
            // 2 expired
            ['num' => 'QUO-2026-0014', 'customer_id' => 15, 'status' => 'expired',  'days_ago' => 120, 'valid_days' => 30],
            ['num' => 'QUO-2026-0015', 'customer_id' => 16, 'status' => 'expired',  'days_ago' => 100, 'valid_days' => 30],
        ];

        // Product sets for line items (product_id => [name, hsn, unit, selling_price, tax_percent])
        $productSets = [
            [1, 3, 11],      // belts, wallet, keychain
            [6, 7, 10],      // polos, denim shirt
            [16, 17, 18],    // oxford, loafer, sandal
            [21, 22, 24],    // tote, laptop bag, handbag
            [8, 9],          // suede jacket, bomber
            [1, 2, 3, 4, 5], // all leather goods
            [12, 13, 14, 15], // accessories
            [21, 22, 23, 24, 25], // all bags
            [6, 7, 8, 10],   // apparel
            [16, 19, 20],    // footwear
            [1, 3, 11, 14],  // mixed leather
            [6, 10, 21],     // mixed
            [22, 24, 25],    // bags
            [8, 16, 22],     // premium items
            [1, 6, 21],      // mixed
        ];

        // Pre-fetch product data
        $products = DB::table('products')
            ->select('id', 'name', 'hsn_code', 'unit', 'selling_price', 'tax_percent')
            ->get()
            ->keyBy('id');

        foreach ($quotations as $i => $q) {
            $quoDate = $now->copy()->subDays($q['days_ago']);
            $validTil = $quoDate->copy()->addDays($q['valid_days']);

            // Insert quotation header (totals will be updated after items)
            $quotationId = DB::table('quotations')->insertGetId([
                'quotation_number' => $q['num'],
                'customer_id' => $q['customer_id'],
                'quotation_date' => $quoDate->toDateString(),
                'valid_until' => $validTil->toDateString(),
                'status' => $q['status'],
                'subtotal' => 0,
                'discount_type' => 'percent',
                'discount_value' => 0,
                'tax_percent' => 0,
                'tax_amount' => 0,
                'grand_total' => 0,
                'terms' => 'Payment due within 30 days. Prices valid as quoted.',
                'notes' => null,
                'created_by' => $salesAdminId,
                'updated_by' => null,
                'deleted_by' => null,
                'created_at' => $quoDate,
                'updated_at' => $quoDate,
                'deleted_at' => null,
            ]);

            // Insert line items
            $productIds = $productSets[$i];
            $subtotal = 0;
            $totalTax = 0;

            foreach ($productIds as $sortOrder => $pid) {
                $p = $products[$pid];
                $qty = rand(2, 20);
                $rate = $p->selling_price;
                $discPct = (rand(0, 3) === 0) ? 5 : 0; // occasional 5% discount
                $lineBeforeDisc = $qty * $rate;
                $lineAfterDisc = $lineBeforeDisc * (1 - $discPct / 100);
                $lineTax = round($lineAfterDisc * $p->tax_percent / 100, 2);
                $lineTotal = round($lineAfterDisc + $lineTax, 2);

                $subtotal += $lineAfterDisc;
                $totalTax += $lineTax;

                DB::table('quotation_items')->insert([
                    'quotation_id' => $quotationId,
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
                    'created_at' => $quoDate,
                    'updated_at' => $quoDate,
                ]);
            }

            $grandTotal = round($subtotal + $totalTax, 2);

            DB::table('quotations')->where('id', $quotationId)->update([
                'subtotal' => round($subtotal, 2),
                'tax_amount' => round($totalTax, 2),
                'grand_total' => $grandTotal,
            ]);
        }
    }
}
