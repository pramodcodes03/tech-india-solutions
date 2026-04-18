<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Adds 12 months of realistic Indian business data for meaningful reports.
 * Covers: Quotations, Sales Orders, Invoices, Payments, Purchase Orders,
 *         Leads, Service Tickets, Stock Movements.
 *
 * Run: php artisan db:seed --class=DummyDataSeeder
 */
class DummyDataSeeder extends Seeder
{
    // ── IDs already in DB ───────────────────────────────────────────────
    private array $customerIds  = [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25];
    private array $productIds   = [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30];
    private array $vendorIds    = [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15];
    private array $warehouseIds = [1,2,3];
    private int   $salesAdmin   = 3;
    private int   $invAdmin     = 4;
    private int   $accAdmin     = 5;

    // ── Product catalogue: id → [name, hsn, unit, purchase_price, sale_price, tax%] ──
    private array $products = [
        1  => ['Genuine Leather Belt - Brown',      '4205', 'pcs', 320,  750,  12],
        2  => ['Genuine Leather Belt - Black',      '4205', 'pcs', 320,  750,  12],
        3  => ['Leather Bifold Wallet - Tan',       '4205', 'pcs', 280,  650,  12],
        4  => ['Leather Trifold Wallet - Black',    '4205', 'pcs', 300,  700,  12],
        5  => ['Leather Passport Cover',            '4205', 'pcs', 180,  420,  12],
        6  => ['Cotton Polo T-Shirt - White',       '6109', 'pcs', 180,  399,   5],
        7  => ['Cotton Polo T-Shirt - Navy',        '6109', 'pcs', 180,  399,   5],
        8  => ['Leather Bomber Jacket - Black',     '6103', 'pcs',1800, 4500,  12],
        9  => ['Slim Fit Denim Jeans - Blue',       '6203', 'pcs', 600, 1299,   5],
        10 => ['Formal Trouser - Grey',             '6203', 'pcs', 550, 1199,   5],
        11 => ['Leather Key Chain',                 '4205', 'pcs',  60,  180,  12],
        12 => ['Leather Gloves - Brown',            '4203', 'pcs', 350,  850,  12],
        13 => ['Woollen Scarf - Beige',             '6117', 'pcs', 220,  599,  12],
        14 => ['Leather Oxford Shoes - Brown',      '6403', 'pcs', 900, 2499,  18],
        15 => ['Leather Loafers - Black',           '6403', 'pcs', 850, 2199,  18],
        16 => ['Chelsea Boots - Dark Brown',        '6403', 'pcs',1100, 2999,  18],
        17 => ['Ladies Handbag - Red',              '4202', 'pcs',1200, 2999,  18],
        18 => ['Ladies Handbag - Tan',              '4202', 'pcs',1200, 2999,  18],
        19 => ['Laptop Bag - Black',                '4202', 'pcs', 800, 1999,  18],
        20 => ['Leather Duffle Bag - Brown',        '4202', 'pcs',1500, 3999,  18],
        21 => ['Suede Jacket - Tan',                '6103', 'pcs',1600, 3999,  12],
        22 => ['Leather Card Holder',               '4205', 'pcs', 120,  299,  12],
        23 => ['Leather Travel Organiser',          '4205', 'pcs', 450, 1099,  12],
        24 => ['Canvas Tote Bag',                   '4202', 'pcs', 180,  499,  18],
        25 => ['Leather Laptop Bag - Tan',          '4202', 'pcs', 950, 2499,  18],
        26 => ['Cowhide Leather Hide (sq ft)',      '4104', 'sqft', 85,   0,    5],
        27 => ['Polyester Lining Fabric (mtr)',     '5407', 'mtr',  35,   0,    5],
        28 => ['Brass Buckles (pcs)',               '8308', 'pcs',   8,   0,   18],
        29 => ['YKK Zippers (pcs)',                 '9607', 'pcs',  12,   0,   18],
        30 => ['Leather Dye - Black (500ml)',       '3212', 'pcs', 280,   0,   18],
    ];

    private array $modes = ['cash','upi','bank_transfer','cheque','card'];

    private int $invCounter  = 9;   // existing invoices: 1-8
    private int $payCounter  = 10;  // existing payments: 1-9
    private int $soCounter   = 11;  // existing SOs: 1-10
    private int $poCounter   = 7;   // existing POs: 1-6
    private int $quoCounter  = 19;  // existing quotations: 1-18
    private int $leadCounter = 11;  // existing leads: 1-10
    private int $stCounter   = 9;   // existing tickets: 1-8

    public function run(): void
    {
        $now = Carbon::now();

        $this->command->info('🌱 Seeding 12 months of Indian business data...');

        $this->seedQuotations($now);
        $this->seedSalesAndInvoices($now);
        $this->seedPayments($now);
        $this->seedPurchaseOrders($now);
        $this->seedLeads($now);
        $this->seedServiceTickets($now);
        $this->seedStockMovements($now);

        $this->command->info('✅ DummyDataSeeder complete.');
    }

    // ─────────────────────────────────────────────────────────────────────
    // QUOTATIONS  (30 new — spread across 12 months)
    // ─────────────────────────────────────────────────────────────────────
    private function seedQuotations(Carbon $now): void
    {
        $definitions = [
            // Month, customer, status, items count, discount%
            [-365, 1,  'accepted',  3, 5],
            [-355, 3,  'accepted',  2, 0],
            [-340, 5,  'rejected',  2, 0],
            [-320, 8,  'accepted',  4, 8],
            [-310, 12, 'expired',   2, 0],
            [-295, 2,  'accepted',  3, 5],
            [-280, 7,  'accepted',  2, 0],
            [-265, 14, 'rejected',  3, 0],
            [-250, 4,  'accepted',  4, 10],
            [-235, 18, 'accepted',  2, 0],
            [-220, 6,  'accepted',  3, 5],
            [-205, 9,  'expired',   2, 0],
            [-190, 11, 'accepted',  3, 8],
            [-175, 15, 'accepted',  2, 0],
            [-160, 20, 'accepted',  4, 5],
            [-145, 22, 'rejected',  2, 0],
            [-130, 3,  'accepted',  3, 0],
            [-115, 13, 'accepted',  2, 5],
            [-100, 16, 'accepted',  3, 10],
            [-85,  24, 'accepted',  2, 0],
            [-70,  1,  'accepted',  4, 5],
            [-55,  5,  'accepted',  2, 0],
            [-45,  8,  'sent',      3, 0],
            [-35,  10, 'sent',      2, 5],
            [-25,  17, 'draft',     3, 0],
            [-20,  19, 'draft',     2, 0],
            [-15,  21, 'sent',      2, 8],
            [-10,  23, 'draft',     3, 0],
            [-5,   25, 'draft',     2, 0],
            [-2,   4,  'draft',     3, 0],
        ];

        $rows = [];
        $itemRows = [];

        foreach ($definitions as $def) {
            [$daysAgo, $customerId, $status, $itemCount, $discPct] = $def;

            $date  = $now->copy()->addDays($daysAgo)->toDateString();
            $valid = $now->copy()->addDays($daysAgo + 30)->toDateString();
            $num   = 'QUO-2026-' . str_pad($this->quoCounter, 4, '0', STR_PAD_LEFT);

            $subtotal = 0;
            $items = $this->randomItems($itemCount);
            foreach ($items as &$item) {
                $subtotal += $item['line_total'];
            }
            unset($item);

            $discAmt   = round($subtotal * $discPct / 100, 2);
            $taxable   = $subtotal - $discAmt;
            $taxPct    = 12;
            $taxAmt    = round($taxable * $taxPct / 100, 2);
            $grandTotal= round($taxable + $taxAmt, 2);

            $quoId = DB::table('quotations')->insertGetId([
                'quotation_number' => $num,
                'customer_id'      => $customerId,
                'quotation_date'   => $date,
                'valid_until'      => $valid,
                'status'           => $status,
                'subtotal'         => $subtotal,
                'discount_type'    => 'percent',
                'discount_value'   => $discPct,
                'tax_percent'      => $taxPct,
                'tax_amount'       => $taxAmt,
                'grand_total'      => $grandTotal,
                'terms'            => 'Payment within 30 days. Goods once sold will not be returned.',
                'notes'            => null,
                'created_by'       => $this->salesAdmin,
                'created_at'       => $date,
                'updated_at'       => $date,
            ]);

            foreach ($items as $sort => $item) {
                DB::table('quotation_items')->insert(array_merge($item, [
                    'quotation_id' => $quoId,
                    'sort_order'   => $sort + 1,
                    'created_at'   => $date,
                    'updated_at'   => $date,
                ]));
            }

            $this->quoCounter++;
        }

        $this->command->info("  Quotations: 30 added");
    }

    // ─────────────────────────────────────────────────────────────────────
    // SALES ORDERS + INVOICES  (50 orders across 12 months)
    // ─────────────────────────────────────────────────────────────────────
    private function seedSalesAndInvoices(Carbon $now): void
    {
        // [daysAgo, customerId, invoiceStatus, itemCount, discPct]
        $orders = [
            [-360, 1,  'paid',     3, 5],
            [-355, 3,  'paid',     2, 0],
            [-348, 5,  'paid',     3, 8],
            [-340, 8,  'paid',     2, 5],
            [-332, 12, 'paid',     4, 0],
            [-325, 2,  'paid',     2, 10],
            [-318, 7,  'paid',     3, 5],
            [-310, 14, 'paid',     2, 0],
            [-303, 4,  'paid',     3, 8],
            [-296, 18, 'paid',     2, 0],

            [-289, 6,  'paid',     3, 5],
            [-282, 9,  'paid',     2, 0],
            [-275, 11, 'paid',     4, 10],
            [-268, 15, 'paid',     2, 5],
            [-261, 20, 'paid',     3, 0],
            [-254, 22, 'paid',     2, 0],
            [-247, 3,  'paid',     3, 8],
            [-240, 13, 'paid',     2, 5],
            [-233, 16, 'paid',     3, 0],
            [-226, 24, 'paid',     2, 0],

            [-219, 1,  'paid',     4, 5],
            [-212, 5,  'paid',     2, 0],
            [-205, 8,  'paid',     3, 10],
            [-198, 10, 'paid',     2, 0],
            [-191, 17, 'paid',     3, 5],
            [-184, 19, 'paid',     2, 8],
            [-177, 21, 'paid',     3, 0],
            [-170, 23, 'paid',     2, 5],
            [-163, 25, 'paid',     3, 0],
            [-156, 4,  'paid',     2, 0],

            [-149, 2,  'partial',  3, 5],
            [-142, 6,  'partial',  2, 0],
            [-135, 9,  'partial',  4, 8],
            [-128, 11, 'partial',  2, 0],
            [-121, 14, 'partial',  3, 5],
            [-114, 16, 'partial',  2, 10],
            [-107, 18, 'partial',  3, 0],
            [-100, 20, 'partial',  2, 5],
            [-93,  22, 'partial',  3, 0],
            [-86,  24, 'partial',  2, 0],

            [-79,  1,  'unpaid',   3, 5],
            [-72,  3,  'unpaid',   2, 0],
            [-65,  7,  'unpaid',   4, 8],
            [-58,  12, 'unpaid',   2, 0],
            [-51,  15, 'unpaid',   3, 5],
            [-44,  19, 'unpaid',   2, 0],
            [-37,  21, 'overdue',  3, 10],
            [-30,  23, 'overdue',  2, 0],
            [-20,  25, 'overdue',  3, 5],
            [-10,  4,  'unpaid',   2, 0],
        ];

        $soCount  = 0;
        $invCount = 0;

        foreach ($orders as $def) {
            [$daysAgo, $customerId, $invStatus, $itemCount, $discPct] = $def;

            $orderDate   = $now->copy()->addDays($daysAgo)->toDateString();
            $invoiceDate = $now->copy()->addDays($daysAgo + 2)->toDateString();
            $dueDate     = $now->copy()->addDays($daysAgo + 32)->toDateString();
            $soNum       = 'SO-2026-' . str_pad($this->soCounter, 4, '0', STR_PAD_LEFT);
            $invNum      = 'INV-2026-' . str_pad($this->invCounter, 4, '0', STR_PAD_LEFT);

            $items     = $this->randomItems($itemCount);
            $subtotal  = array_sum(array_column($items, 'line_total'));
            $discAmt   = round($subtotal * $discPct / 100, 2);
            $taxable   = round($subtotal - $discAmt, 2);
            $taxPct    = 12;
            $taxAmt    = round($taxable * $taxPct / 100, 2);
            $grandTotal= round($taxable + $taxAmt, 2);

            $soStatus  = match($invStatus) {
                'paid'             => 'delivered',
                'partial'          => 'shipped',
                'unpaid', 'overdue'=> 'confirmed',
                default            => 'processing',
            };

            // Sales Order
            $soId = DB::table('sales_orders')->insertGetId([
                'order_number'   => $soNum,
                'customer_id'    => $customerId,
                'quotation_id'   => null,
                'order_date'     => $orderDate,
                'status'         => $soStatus,
                'subtotal'       => $subtotal,
                'discount_type'  => 'percent',
                'discount_value' => $discPct,
                'tax_percent'    => $taxPct,
                'tax_amount'     => $taxAmt,
                'grand_total'    => $grandTotal,
                'terms'          => 'Delivery within 7-10 working days.',
                'notes'          => null,
                'created_by'     => $this->salesAdmin,
                'created_at'     => $orderDate,
                'updated_at'     => $orderDate,
            ]);

            foreach ($items as $sort => $item) {
                DB::table('sales_order_items')->insert(array_merge($item, [
                    'sales_order_id' => $soId,
                    'sort_order'     => $sort + 1,
                    'created_at'     => $orderDate,
                    'updated_at'     => $orderDate,
                ]));
            }

            // Invoice
            $amtPaid = match($invStatus) {
                'paid'    => $grandTotal,
                'partial' => round($grandTotal * (rand(30, 65) / 100), 2),
                'overdue' => round($grandTotal * (rand(0, 20) / 100), 2),
                default   => 0,
            };
            $balanceDue = round($grandTotal - $amtPaid, 2);

            $invId = DB::table('invoices')->insertGetId([
                'invoice_number' => $invNum,
                'customer_id'    => $customerId,
                'sales_order_id' => $soId,
                'invoice_date'   => $invoiceDate,
                'due_date'       => $dueDate,
                'subtotal'       => $subtotal,
                'discount_type'  => 'percent',
                'discount_value' => $discPct,
                'tax_percent'    => $taxPct,
                'tax_amount'     => $taxAmt,
                'grand_total'    => $grandTotal,
                'amount_paid'    => $amtPaid,
                'balance_due'    => $balanceDue,
                'status'         => $invStatus,
                'terms'          => 'Payment due within 30 days. Late payment may attract 2% interest per month.',
                'notes'          => null,
                'created_by'     => $this->accAdmin,
                'created_at'     => $invoiceDate,
                'updated_at'     => $invoiceDate,
            ]);

            foreach ($items as $sort => $item) {
                DB::table('invoice_items')->insert(array_merge($item, [
                    'invoice_id' => $invId,
                    'sort_order' => $sort + 1,
                    'created_at' => $invoiceDate,
                    'updated_at' => $invoiceDate,
                ]));
            }

            $this->soCounter++;
            $this->invCounter++;
            $soCount++;
            $invCount++;
        }

        $this->command->info("  Sales Orders: {$soCount} added | Invoices: {$invCount} added");
    }

    // ─────────────────────────────────────────────────────────────────────
    // PAYMENTS  (for all paid/partial/overdue invoices added above)
    // ─────────────────────────────────────────────────────────────────────
    private function seedPayments(Carbon $now): void
    {
        $invoices = DB::table('invoices')
            ->where('id', '>', 8)  // skip original 8
            ->whereIn('status', ['paid', 'partial', 'overdue'])
            ->get();

        $count = 0;

        foreach ($invoices as $inv) {
            if ($inv->amount_paid <= 0) continue;

            $invDate = Carbon::parse($inv->invoice_date);

            if ($inv->status === 'paid') {
                // 1 or 2 payments
                $split = rand(0, 1);
                if ($split && $inv->grand_total > 2000) {
                    $first  = round($inv->grand_total * (rand(40, 60) / 100), 2);
                    $second = round($inv->grand_total - $first, 2);
                    $this->insertPayment($inv->id, $inv->customer_id, $first, $invDate->copy()->addDays(5));
                    $this->insertPayment($inv->id, $inv->customer_id, $second, $invDate->copy()->addDays(20));
                    $count += 2;
                } else {
                    $this->insertPayment($inv->id, $inv->customer_id, $inv->grand_total, $invDate->copy()->addDays(rand(5, 25)));
                    $count++;
                }
            } else {
                // partial or overdue: 1 payment
                $this->insertPayment($inv->id, $inv->customer_id, $inv->amount_paid, $invDate->copy()->addDays(rand(5, 15)));
                $count++;
            }
        }

        $this->command->info("  Payments: {$count} added");
    }

    private function insertPayment(int $invId, int $custId, float $amount, Carbon $date): void
    {
        $num = 'PAY-2026-' . str_pad($this->payCounter, 4, '0', STR_PAD_LEFT);
        DB::table('payments')->insert([
            'payment_number' => $num,
            'invoice_id'     => $invId,
            'customer_id'    => $custId,
            'payment_date'   => $date->toDateString(),
            'amount'         => $amount,
            'mode'           => $this->modes[array_rand($this->modes)],
            'reference_no'   => strtoupper(substr(md5(uniqid()), 0, 10)),
            'notes'          => null,
            'created_by'     => $this->accAdmin,
            'created_at'     => $date->toDateString(),
            'updated_at'     => $date->toDateString(),
        ]);
        $this->payCounter++;
    }

    // ─────────────────────────────────────────────────────────────────────
    // PURCHASE ORDERS  (25 POs spread across 12 months)
    // ─────────────────────────────────────────────────────────────────────
    private function seedPurchaseOrders(Carbon $now): void
    {
        $pos = [
            [-360, 1,  'received', 3],
            [-345, 3,  'received', 2],
            [-330, 5,  'received', 3],
            [-315, 8,  'received', 2],
            [-300, 2,  'received', 4],
            [-285, 4,  'received', 3],
            [-270, 6,  'received', 2],
            [-255, 9,  'received', 3],
            [-240, 11, 'received', 2],
            [-225, 13, 'received', 4],
            [-210, 1,  'received', 3],
            [-195, 3,  'received', 2],
            [-180, 7,  'received', 3],
            [-165, 10, 'partial',  2],
            [-150, 12, 'partial',  3],
            [-135, 15, 'partial',  2],
            [-120, 2,  'partial',  4],
            [-105, 4,  'received', 3],
            [-90,  6,  'received', 2],
            [-75,  8,  'sent',     3],
            [-60,  1,  'sent',     2],
            [-45,  5,  'sent',     3],
            [-30,  9,  'draft',    2],
            [-15,  11, 'draft',    3],
            [-5,   13, 'draft',    2],
        ];

        $count = 0;
        foreach ($pos as $def) {
            [$daysAgo, $vendorId, $status, $itemCount] = $def;

            $poDate  = $now->copy()->addDays($daysAgo)->toDateString();
            $expDate = $now->copy()->addDays($daysAgo + 14)->toDateString();
            $poNum   = 'PO-2026-' . str_pad($this->poCounter, 4, '0', STR_PAD_LEFT);

            // Use raw material products (26-30) or finished goods mix
            $rawIds  = [26, 27, 28, 29, 30];
            $selIds  = array_slice($rawIds, 0, min($itemCount, 5));
            if (count($selIds) < $itemCount) {
                $extra = array_slice($this->productIds, 0, $itemCount - count($selIds));
                $selIds = array_merge($selIds, $extra);
            }

            $items    = [];
            $subtotal = 0;
            foreach (array_slice($selIds, 0, $itemCount) as $pid) {
                $p      = $this->products[$pid] ?? $this->products[1];
                $qty    = rand(10, 100);
                $rate   = $p[3]; // purchase price
                $taxPct = $p[5];
                $gross  = round($qty * $rate, 2);
                $taxAmt = round($gross * $taxPct / 100, 2);
                $total  = round($gross + $taxAmt, 2);
                $subtotal += $gross;
                $items[] = [
                    'product_id'       => $pid,
                    'description'      => $p[0],
                    'hsn_code'         => $p[1],
                    'quantity'         => $qty,
                    'unit'             => $p[2],
                    'rate'             => $rate,
                    'discount_percent' => 0,
                    'tax_percent'      => $taxPct,
                    'line_total'       => $total,
                ];
            }

            $taxAmt    = round($subtotal * 12 / 100, 2);
            $grandTotal= round($subtotal + $taxAmt, 2);

            $poId = DB::table('purchase_orders')->insertGetId([
                'po_number'      => $poNum,
                'vendor_id'      => $vendorId,
                'po_date'        => $poDate,
                'expected_date'  => $expDate,
                'status'         => $status,
                'subtotal'       => $subtotal,
                'discount_type'  => 'percent',
                'discount_value' => 0,
                'tax_percent'    => 12,
                'tax_amount'     => $taxAmt,
                'grand_total'    => $grandTotal,
                'terms'          => 'Delivery within 14 days. Quality check mandatory on receipt.',
                'notes'          => null,
                'created_by'     => $this->invAdmin,
                'created_at'     => $poDate,
                'updated_at'     => $poDate,
            ]);

            foreach ($items as $sort => $item) {
                DB::table('purchase_order_items')->insert(array_merge($item, [
                    'purchase_order_id' => $poId,
                    'sort_order'        => $sort + 1,
                    'created_at'        => $poDate,
                    'updated_at'        => $poDate,
                ]));
            }

            $this->poCounter++;
            $count++;
        }

        $this->command->info("  Purchase Orders: {$count} added");
    }

    // ─────────────────────────────────────────────────────────────────────
    // LEADS  (40 leads across 12 months, Indian sources)
    // ─────────────────────────────────────────────────────────────────────
    private function seedLeads(Carbon $now): void
    {
        $indianNames = [
            'Arjun Sharma','Priya Mehta','Rahul Singh','Sunita Verma','Vikram Nair',
            'Ananya Iyer','Karan Patel','Deepika Reddy','Suresh Rao','Kavya Krishnan',
            'Rohit Gupta','Meena Joshi','Amit Chaudhary','Ritu Saxena','Sanjay Banerjee',
            'Pooja Agarwal','Nikhil Tiwari','Divya Menon','Aakash Yadav','Sneha Pillai',
            'Tarun Bose','Nandini Desai','Pranav Shah','Isha Malhotra','Vivek Pandey',
            'Lakshmi Subramaniam','Gaurav Jain','Smita Patil','Abhishek Chauhan','Rekha Nambiar',
            'Dinesh Shetty','Priyanka Kapoor','Manish Tripathi','Swati Bhatt','Ajay Kulkarni',
            'Anita Srivastava','Rajesh Mishra','Lalitha Narayanan','Hemant Dwivedi','Geeta Sharma',
        ];
        $companies = [
            'Tata Consumer Products','Reliance Retail','Infosys Ltd','Wipro Technologies',
            'Mahindra & Mahindra','HCL Technologies','Bajaj Auto','Hindustan Unilever',
            'HDFC Bank','ICICI Bank','State Bank of India','Larsen & Toubro',
            'Asian Paints','ITC Limited','Bharti Airtel','ONGC','Coal India','NTPC',
            'Power Grid Corp','Maruti Suzuki','Hero MotoCorp','Ultratech Cement',
            'Grasim Industries','Titan Company','Nestle India','Britannia Industries',
            'Dabur India','Marico','Godrej Consumer','Pidilite Industries',
        ];
        $sources = ['website','referral','cold_call','exhibition','social_media','email','walk_in','partner'];
        $statuses = ['new','contacted','qualified','proposal','negotiation','won','lost'];
        $statusWeights = [5,8,6,8,5,10,8]; // weighted distribution

        $count = 0;
        foreach ($indianNames as $i => $name) {
            $daysAgo = -(rand(1, 365));
            $date    = $now->copy()->addDays($daysAgo)->toDateString();
            $code    = 'LEAD-' . str_pad($this->leadCounter, 4, '0', STR_PAD_LEFT);

            // weighted status selection
            $pool = [];
            foreach ($statuses as $j => $s) {
                for ($k = 0; $k < $statusWeights[$j]; $k++) $pool[] = $s;
            }
            $status = $pool[array_rand($pool)];

            $expValue = rand(10, 100) * 5000; // 50k to 5L

            $followUp = ($status === 'new' || $status === 'contacted')
                ? $now->copy()->addDays(rand(1, 14))->toDateString()
                : null;

            DB::table('leads')->insert([
                'code'            => $code,
                'name'            => $name,
                'company'         => $companies[array_rand($companies)],
                'phone'           => '9' . rand(100000000, 999999999),
                'email'           => strtolower(str_replace(' ', '.', $name)) . '@example.com',
                'source'          => $sources[array_rand($sources)],
                'status'          => $status,
                'assigned_to'     => $this->salesAdmin,
                'expected_value'  => $expValue,
                'next_follow_up_at' => $followUp,
                'notes'           => 'Interested in leather accessories and corporate gifting.',
                'created_by'      => $this->salesAdmin,
                'created_at'      => $date,
                'updated_at'      => $date,
            ]);

            $this->leadCounter++;
            $count++;
        }

        $this->command->info("  Leads: {$count} added");
    }

    // ─────────────────────────────────────────────────────────────────────
    // SERVICE TICKETS  (30 tickets)
    // ─────────────────────────────────────────────────────────────────────
    private function seedServiceTickets(Carbon $now): void
    {
        $issues = [
            'Zipper on bag not working properly',
            'Stitching coming apart on leather belt',
            'Colour fading on leather jacket',
            'Wrong size delivered — need exchange',
            'Product damaged during transit',
            'Buckle broken on belt',
            'Sole detaching from leather shoes',
            'Wallet stitching loose at edges',
            'Incorrect item delivered against order',
            'Leather surface scratched on delivery',
            'Handle of handbag broken',
            'Lining torn inside laptop bag',
            'Zip pull missing on duffle bag',
            'Size mismatch — ordered L, received M',
            'Colour not matching as shown on website',
            'Leather cracking after 2 weeks of use',
            'Metal clasp not functioning on wallet',
            'Product quality not as described',
            'Missing accessories in packaging',
            'Seams splitting on travel organiser',
            'Dye transfer on leather gloves',
            'Key ring broke off keychain',
            'Oxford shoes causing discomfort',
            'Luggage tag missing from duffle bag',
            'Water stain on leather jacket',
            'Monogram engraving incorrect',
            'Replacement belt loop required',
            'Passport cover spine cracking',
            'Card holder delaminating',
            'Scarf with manufacturing defect',
        ];

        $priorities = ['low','medium','high','urgent'];
        $statuses   = ['open','in_progress','resolved','closed'];
        $statusW    = [6, 8, 8, 8]; // weights

        $pool = [];
        foreach ($statuses as $j => $s) {
            for ($k = 0; $k < $statusW[$j]; $k++) $pool[] = $s;
        }

        $count = 0;
        foreach ($issues as $i => $issue) {
            $daysAgo  = -(rand(1, 300));
            $openedAt = $now->copy()->addDays($daysAgo)->toDateString();
            $status   = $pool[array_rand($pool)];
            $closedAt = in_array($status, ['resolved','closed'])
                ? $now->copy()->addDays($daysAgo + rand(3, 15))->toDateString()
                : null;
            $ticketNum = 'TKT-2026-' . str_pad($this->stCounter, 4, '0', STR_PAD_LEFT);
            $priority  = $priorities[array_rand($priorities)];
            $custId    = $this->customerIds[array_rand($this->customerIds)];
            $prodId    = $this->productIds[array_rand(array_slice($this->productIds, 0, 20))]; // only finished goods

            DB::table('service_tickets')->insert([
                'ticket_number'    => $ticketNum,
                'customer_id'      => $custId,
                'product_id'       => $prodId,
                'issue_description'=> $issue,
                'priority'         => $priority,
                'status'           => $status,
                'assigned_to'      => 6, // service admin
                'opened_at'        => $openedAt,
                'closed_at'        => $closedAt,
                'resolution_notes' => $closedAt ? 'Issue resolved. Replacement dispatched / repair completed.' : null,
                'created_by'       => 6,
                'created_at'       => $openedAt,
                'updated_at'       => $closedAt ?? $openedAt,
            ]);

            $this->stCounter++;
            $count++;
        }

        $this->command->info("  Service Tickets: {$count} added");
    }

    // ─────────────────────────────────────────────────────────────────────
    // STOCK MOVEMENTS  (additional movements for inventory report depth)
    // ─────────────────────────────────────────────────────────────────────
    private function seedStockMovements(Carbon $now): void
    {
        $types = ['in','out','adjustment'];
        $count = 0;

        for ($month = 12; $month >= 1; $month--) {
            $baseDate = $now->copy()->subMonths($month);

            // 5 movements per month per warehouse
            foreach ($this->warehouseIds as $wid) {
                for ($m = 0; $m < 5; $m++) {
                    $pid  = $this->productIds[array_rand($this->productIds)];
                    $weighted = [0,0,0,1,1,2]; $type = $types[$weighted[array_rand($weighted)]];
                    $qty  = $type === 'adjustment' ? rand(-5, 10) : rand(5, 50);
                    if ($type === 'out') $qty = -abs($qty);
                    $date = $baseDate->copy()->addDays(rand(1, 28))->toDateString();

                    DB::table('stock_movements')->insert([
                        'product_id'     => $pid,
                        'warehouse_id'   => $wid,
                        'type'           => $type,
                        'quantity'       => $qty,
                        'reference_type' => $type === 'in' ? 'purchase_order' : ($type === 'out' ? 'sales_order' : null),
                        'reference_id'   => null,
                        'notes'          => ucfirst($type) . ' movement for warehouse #' . $wid,
                        'created_by'     => $this->invAdmin,
                        'created_at'     => $date,
                    ]);
                    $count++;
                }
            }
        }

        $this->command->info("  Stock Movements: {$count} added");
    }

    // ─────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────
    private function randomItems(int $count): array
    {
        $pids  = array_slice($this->productIds, 0, 25); // finished goods only
        shuffle($pids);
        $pids  = array_slice($pids, 0, $count);
        $items = [];

        foreach ($pids as $pid) {
            $p      = $this->products[$pid] ?? $this->products[1];
            $qty    = rand(1, 10);
            $rate   = $p[4]; // sale price
            $taxPct = $p[5];
            $gross  = round($qty * $rate, 2);
            $taxAmt = round($gross * $taxPct / 100, 2);
            $total  = round($gross + $taxAmt, 2);

            $items[] = [
                'product_id'       => $pid,
                'description'      => $p[0],
                'hsn_code'         => $p[1],
                'quantity'         => $qty,
                'unit'             => $p[2],
                'rate'             => $rate,
                'discount_percent' => 0,
                'tax_percent'      => $taxPct,
                'line_total'       => $total,
            ];
        }

        return $items;
    }
}
