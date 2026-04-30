<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $businessId = app(\App\Support\Tenancy\CurrentBusiness::class)->id();
        $now = Carbon::now();
        $accountsAdminId = 5; // Lakshmi - Accounts

        // Map invoices to sales orders
        // SO-1 (delivered), SO-2 (shipped), SO-3 (shipped), SO-4 (processing), SO-5 (processing), SO-6 (confirmed), SO-8 (pending), SO-9 (pending)
        // NOTE: sales_order_number values map to order_number, resolved per-business below.
        $invoices = [
            // 3 paid - linked to delivered/shipped SOs
            ['num' => 'INV-2026-0001', 'sales_order_number' => 'SO-2026-0001', 'status' => 'paid',    'days_ago' => 75, 'due_days' => 30],
            ['num' => 'INV-2026-0002', 'sales_order_number' => 'SO-2026-0002', 'status' => 'paid',    'days_ago' => 60, 'due_days' => 30],
            ['num' => 'INV-2026-0003', 'sales_order_number' => 'SO-2026-0003', 'status' => 'paid',    'days_ago' => 45, 'due_days' => 30],
            // 2 partial - linked to processing SOs
            ['num' => 'INV-2026-0004', 'sales_order_number' => 'SO-2026-0004', 'status' => 'partial', 'days_ago' => 30, 'due_days' => 30],
            ['num' => 'INV-2026-0005', 'sales_order_number' => 'SO-2026-0005', 'status' => 'partial', 'days_ago' => 20, 'due_days' => 30],
            // 2 unpaid
            ['num' => 'INV-2026-0006', 'sales_order_number' => 'SO-2026-0006', 'status' => 'unpaid',  'days_ago' => 12, 'due_days' => 30],
            ['num' => 'INV-2026-0007', 'sales_order_number' => 'SO-2026-0007', 'status' => 'unpaid',  'days_ago' => 8,  'due_days' => 30],
            // 1 overdue
            ['num' => 'INV-2026-0008', 'sales_order_number' => 'SO-2026-0008', 'status' => 'overdue', 'days_ago' => 45, 'due_days' => 15],
        ];

        foreach ($invoices as $inv) {
            $invDate = $now->copy()->subDays($inv['days_ago']);
            $dueDate = $invDate->copy()->addDays($inv['due_days']);

            // Get SO data (per-business lookup by order_number)
            $so = DB::table('sales_orders')
                ->where('business_id', $businessId)
                ->where('order_number', $inv['sales_order_number'])
                ->first();
            $customerId = $so->customer_id;
            $resolvedSalesOrderId = $so->id;

            $invoiceId = DB::table('invoices')->insertGetId([
                'business_id' => $businessId,
                'invoice_number' => $inv['num'],
                'customer_id' => $customerId,
                'sales_order_id' => $resolvedSalesOrderId,
                'invoice_date' => $invDate->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'subtotal' => $so->subtotal,
                'discount_type' => $so->discount_type,
                'discount_value' => $so->discount_value,
                'tax_percent' => $so->tax_percent,
                'tax_amount' => $so->tax_amount,
                'grand_total' => $so->grand_total,
                'amount_paid' => 0,
                'balance_due' => $so->grand_total,
                'status' => $inv['status'],
                'terms' => 'Payment due within 30 days from invoice date.',
                'notes' => null,
                'created_by' => $accountsAdminId,
                'updated_by' => null,
                'deleted_by' => null,
                'created_at' => $invDate,
                'updated_at' => $invDate,
                'deleted_at' => null,
            ]);

            // Copy SO items as invoice items (per-business resolved id)
            $soItems = DB::table('sales_order_items')
                ->where('business_id', $businessId)
                ->where('sales_order_id', $resolvedSalesOrderId)
                ->get();

            foreach ($soItems as $si) {
                DB::table('invoice_items')->insert([
                    'business_id' => $businessId,
                    'invoice_id' => $invoiceId,
                    'product_id' => $si->product_id,
                    'description' => $si->description,
                    'hsn_code' => $si->hsn_code,
                    'quantity' => $si->quantity,
                    'unit' => $si->unit,
                    'rate' => $si->rate,
                    'discount_percent' => $si->discount_percent,
                    'tax_percent' => $si->tax_percent,
                    'line_total' => $si->line_total,
                    'sort_order' => $si->sort_order,
                    'created_at' => $invDate,
                    'updated_at' => $invDate,
                ]);
            }
        }
    }
}
