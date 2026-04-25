<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Models\ServiceTicket;
use App\Models\StockMovement;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardsController extends Controller
{
    // ════════════════════════════════════════════════════════════════════
    // 1. SALES DASHBOARD
    // ════════════════════════════════════════════════════════════════════
    public function sales()
    {
        abort_unless(Auth::guard('admin')->user()->can('leads.view'), 403);

        $today = Carbon::today();
        $monthStart = $today->copy()->startOfMonth();

        // KPI cards
        $kpi = [
            'leads_total'       => Lead::count(),
            'leads_open'        => Lead::whereIn('status', ['new', 'contacted', 'qualified', 'proposal'])->count(),
            'leads_won'         => Lead::where('status', 'won')->count(),
            'pipeline_value'    => (float) Lead::whereIn('status', ['new', 'contacted', 'qualified', 'proposal'])->sum('expected_value'),
            'quotes_total'      => Quotation::count(),
            'quotes_accepted'   => Quotation::where('status', 'accepted')->count(),
            'revenue_mtd'       => (float) Invoice::where('invoice_date', '>=', $monthStart)->sum('grand_total'),
            'paid_mtd'          => (float) Payment::where('payment_date', '>=', $monthStart)->sum('amount'),
            'receivables'       => (float) Invoice::whereIn('status', ['unpaid', 'partial', 'overdue'])->sum('balance_due'),
            'customers_total'   => Customer::where('status', 'active')->count(),
        ];
        $kpi['quote_acceptance_rate'] = $kpi['quotes_total'] > 0
            ? round(($kpi['quotes_accepted'] / $kpi['quotes_total']) * 100, 1)
            : 0;
        $kpi['win_rate'] = $kpi['leads_total'] > 0
            ? round(($kpi['leads_won'] / $kpi['leads_total']) * 100, 1)
            : 0;

        // Lead funnel (Lead → Quote → SalesOrder → Invoice → Paid)
        $funnel = [
            ['stage' => 'Leads',       'value' => Lead::count()],
            ['stage' => 'Qualified',   'value' => Lead::whereIn('status', ['qualified', 'proposal', 'won'])->count()],
            ['stage' => 'Quoted',      'value' => Quotation::count()],
            ['stage' => 'Orders',      'value' => SalesOrder::whereNotIn('status', ['cancelled'])->count()],
            ['stage' => 'Invoiced',    'value' => Invoice::count()],
            ['stage' => 'Paid',        'value' => Invoice::where('status', 'paid')->count()],
        ];

        // Lead source donut
        $leadBySource = Lead::select('source', DB::raw('COUNT(*) as total'))
            ->groupBy('source')->pluck('total', 'source')->toArray();

        // Lead source × value (bubble: x=count, y=avg value, z=total value)
        $leadSourceBubble = Lead::select('source',
                DB::raw('COUNT(*) as cnt'),
                DB::raw('AVG(expected_value) as avg_val'),
                DB::raw('SUM(expected_value) as total_val'))
            ->groupBy('source')->get();

        // Revenue trend last 12 months (invoiced vs paid)
        $revenueTrend = [];
        for ($i = 11; $i >= 0; $i--) {
            $m = Carbon::today()->subMonths($i);
            $revenueTrend[] = [
                'label'    => $m->format('M Y'),
                'invoiced' => (float) Invoice::whereYear('invoice_date', $m->year)->whereMonth('invoice_date', $m->month)->sum('grand_total'),
                'paid'     => (float) Payment::whereYear('payment_date', $m->year)->whereMonth('payment_date', $m->month)->sum('amount'),
            ];
        }

        // Invoice status treemap
        $invoiceStatus = Invoice::select('status', DB::raw('COUNT(*) as cnt'), DB::raw('SUM(grand_total) as total'))
            ->groupBy('status')->get();

        // Quotation status over months (stacked column)
        $quoteStatusMonthly = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = Carbon::today()->subMonths($i);
            $row = ['label' => $m->format('M Y')];
            foreach (['draft', 'sent', 'accepted', 'rejected', 'expired'] as $s) {
                $row[$s] = Quotation::where('status', $s)
                    ->whereYear('quotation_date', $m->year)
                    ->whereMonth('quotation_date', $m->month)
                    ->count();
            }
            $quoteStatusMonthly[] = $row;
        }

        // Top customers
        $topCustomers = Customer::withSum('invoices', 'grand_total')
            ->orderByDesc('invoices_sum_grand_total')
            ->limit(10)->get();

        // DSO gauge (days sales outstanding = avg days from invoice_date to first payment for paid invoices over last 90 days)
        $dsoRow = DB::table('payments')
            ->join('invoices', 'invoices.id', '=', 'payments.invoice_id')
            ->where('payments.payment_date', '>=', $today->copy()->subDays(90))
            ->selectRaw('AVG(DATEDIFF(payments.payment_date, invoices.invoice_date)) as dso')
            ->first();
        $dso = (int) round($dsoRow->dso ?? 0);

        // Invoice aging buckets (unpaid invoices)
        $aging = ['0-30' => 0, '31-60' => 0, '61-90' => 0, '90+' => 0];
        Invoice::whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->select('invoice_date', 'balance_due')->chunk(500, function ($c) use (&$aging) {
                foreach ($c as $inv) {
                    $d = Carbon::parse($inv->invoice_date)->diffInDays(now());
                    $amt = (float) $inv->balance_due;
                    if ($d <= 30) $aging['0-30'] += $amt;
                    elseif ($d <= 60) $aging['31-60'] += $amt;
                    elseif ($d <= 90) $aging['61-90'] += $amt;
                    else $aging['90+'] += $amt;
                }
            });

        // Recent activity
        $recentLeads = Lead::latest()->limit(5)->get();
        $overdueInvoices = Invoice::with('customer')->where('status', 'overdue')
            ->orWhere(function ($q) { $q->whereIn('status', ['unpaid', 'partial'])->where('due_date', '<', now()); })
            ->limit(5)->get();

        return view('admin.dashboards.sales', compact(
            'kpi', 'funnel', 'leadBySource', 'leadSourceBubble', 'revenueTrend',
            'invoiceStatus', 'quoteStatusMonthly', 'topCustomers', 'dso', 'aging',
            'recentLeads', 'overdueInvoices'
        ));
    }

    // ════════════════════════════════════════════════════════════════════
    // 2. SERVICE / SUPPORT DASHBOARD
    // ════════════════════════════════════════════════════════════════════
    public function service()
    {
        abort_unless(Auth::guard('admin')->user()->can('service_tickets.view'), 403);

        $today = Carbon::today();
        $monthStart = $today->copy()->startOfMonth();

        $kpi = [
            'total'       => ServiceTicket::count(),
            'open'        => ServiceTicket::where('status', 'open')->count(),
            'in_progress' => ServiceTicket::where('status', 'in_progress')->count(),
            'resolved'    => ServiceTicket::where('status', 'resolved')->count(),
            'closed'      => ServiceTicket::where('status', 'closed')->count(),
            'critical'    => ServiceTicket::where('priority', 'critical')->whereIn('status', ['open', 'in_progress'])->count(),
            'mtd_opened'  => ServiceTicket::where('opened_at', '>=', $monthStart)->count(),
            'mtd_closed'  => ServiceTicket::where('closed_at', '>=', $monthStart)->count(),
        ];

        // Avg resolution time (hours) by priority
        $resByPriority = ServiceTicket::whereNotNull('closed_at')
            ->select('priority', DB::raw('AVG(TIMESTAMPDIFF(HOUR, opened_at, closed_at)) as avg_hrs'), DB::raw('COUNT(*) as cnt'))
            ->groupBy('priority')->get();

        // SLA target hours per priority
        $slaTargets = ['critical' => 4, 'high' => 24, 'medium' => 72, 'low' => 168];
        $slaCompliant = 0; $slaTotal = 0;
        ServiceTicket::whereNotNull('closed_at')
            ->where('closed_at', '>=', $today->copy()->subDays(90))
            ->select('priority', 'opened_at', 'closed_at')->chunk(500, function ($c) use (&$slaCompliant, &$slaTotal, $slaTargets) {
                foreach ($c as $t) {
                    $target = $slaTargets[$t->priority] ?? 72;
                    $hrs = Carbon::parse($t->opened_at)->diffInHours($t->closed_at);
                    $slaTotal++;
                    if ($hrs <= $target) $slaCompliant++;
                }
            });
        $slaRate = $slaTotal > 0 ? round(($slaCompliant / $slaTotal) * 100, 1) : 0;

        // Volume trend last 12 months (opened vs closed)
        $volumeTrend = [];
        for ($i = 11; $i >= 0; $i--) {
            $m = Carbon::today()->subMonths($i);
            $volumeTrend[] = [
                'label'  => $m->format('M Y'),
                'opened' => ServiceTicket::whereYear('opened_at', $m->year)->whereMonth('opened_at', $m->month)->count(),
                'closed' => ServiceTicket::whereYear('closed_at', $m->year)->whereMonth('closed_at', $m->month)->count(),
            ];
        }

        // Priority donut
        $byPriority = ServiceTicket::select('priority', DB::raw('COUNT(*) as cnt'))
            ->groupBy('priority')->pluck('cnt', 'priority')->toArray();

        // Category breakdown
        $byCategory = DB::table('service_tickets')
            ->leftJoin('service_categories', 'service_categories.id', '=', 'service_tickets.category_id')
            ->select(DB::raw('COALESCE(service_categories.name, "Uncategorized") as name'), DB::raw('COUNT(*) as cnt'))
            ->groupBy('service_categories.id', 'service_categories.name')
            ->orderByDesc('cnt')->limit(8)->get();

        // Technician workload heatmap (technician × day of week, last 60 days)
        $technicians = DB::table('service_tickets')
            ->join('admins', 'admins.id', '=', 'service_tickets.assigned_to')
            ->where('service_tickets.opened_at', '>=', $today->copy()->subDays(60))
            ->select('admins.name', DB::raw('DAYOFWEEK(opened_at) as dow'), DB::raw('COUNT(*) as cnt'))
            ->groupBy('admins.id', 'admins.name', 'dow')->get()
            ->groupBy('name');

        $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        $heatmap = [];
        foreach ($technicians as $name => $rows) {
            $pts = array_fill(0, 7, 0);
            foreach ($rows as $r) $pts[$r->dow - 1] = (int) $r->cnt;
            $heatmap[] = ['name' => $name, 'data' => $pts];
        }

        // Status flow counts (sankey-style: open→in_progress, in_progress→resolved, resolved→closed)
        $flow = [
            'open'        => ServiceTicket::where('status', 'open')->count(),
            'in_progress' => ServiceTicket::where('status', 'in_progress')->count(),
            'resolved'    => ServiceTicket::where('status', 'resolved')->count(),
            'closed'      => ServiceTicket::where('status', 'closed')->count(),
        ];

        // Recent critical tickets
        $criticalTickets = ServiceTicket::with(['customer'])
            ->where('priority', 'critical')
            ->whereIn('status', ['open', 'in_progress'])
            ->latest('opened_at')->limit(6)->get();

        return view('admin.dashboards.service', compact(
            'kpi', 'resByPriority', 'slaRate', 'slaCompliant', 'slaTotal',
            'volumeTrend', 'byPriority', 'byCategory', 'heatmap', 'days',
            'flow', 'criticalTickets'
        ));
    }

    // ════════════════════════════════════════════════════════════════════
    // 3. INVENTORY DASHBOARD
    // ════════════════════════════════════════════════════════════════════
    public function inventory()
    {
        abort_unless(Auth::guard('admin')->user()->can('products.view'), 403);

        $stockSum = "COALESCE(SUM(CASE WHEN sm.type IN ('in','adjustment') THEN sm.quantity ELSE -sm.quantity END), 0)";

        // Per-product current stock + valuation
        $productStock = DB::table('products as p')
            ->leftJoin('stock_movements as sm', 'sm.product_id', '=', 'p.id')
            ->leftJoin('product_categories as c', 'c.id', '=', 'p.category_id')
            ->where('p.status', 'active')
            ->select('p.id', 'p.name', 'p.code', 'p.purchase_price', 'p.reorder_level',
                'c.id as cat_id', 'c.name as cat_name',
                DB::raw("$stockSum as current_stock"))
            ->groupBy('p.id', 'p.name', 'p.code', 'p.purchase_price', 'p.reorder_level', 'c.id', 'c.name')
            ->get();

        $totalSkus = $productStock->count();
        $totalUnits = (int) $productStock->sum('current_stock');
        $totalValue = $productStock->sum(fn ($r) => (float) $r->purchase_price * max((float) $r->current_stock, 0));
        $belowReorder = $productStock->filter(fn ($r) => (float) $r->reorder_level > 0 && (float) $r->current_stock <= (float) $r->reorder_level)->count();
        $outOfStock = $productStock->filter(fn ($r) => (float) $r->current_stock <= 0)->count();
        $reorderPct = $totalSkus > 0 ? round(($belowReorder / $totalSkus) * 100, 1) : 0;

        $kpi = [
            'total_skus'    => $totalSkus,
            'total_units'   => $totalUnits,
            'total_value'   => $totalValue,
            'below_reorder' => $belowReorder,
            'out_of_stock'  => $outOfStock,
            'reorder_pct'   => $reorderPct,
            'categories'    => $productStock->pluck('cat_id')->filter()->unique()->count(),
            'warehouses'    => DB::table('warehouses')->where('is_default', '<=', 1)->count(),
        ];

        // Treemap: category value
        $byCategory = $productStock->groupBy('cat_name')
            ->map(fn ($rows, $name) => [
                'name'  => $name ?: 'Uncategorized',
                'units' => (int) $rows->sum('current_stock'),
                'value' => (float) $rows->sum(fn ($r) => (float) $r->purchase_price * max((float) $r->current_stock, 0)),
                'skus'  => $rows->count(),
            ])->values();

        // Stock movement heatmap (top 10 products × last 6 months, total movement = in+out)
        $topProductIds = $productStock->sortByDesc(fn ($r) => abs((float) $r->current_stock))->take(10)->pluck('id')->toArray();
        $movementMatrix = [];
        if (! empty($topProductIds)) {
            $start = Carbon::today()->subMonths(5)->startOfMonth();
            $rows = DB::table('stock_movements')
                ->whereIn('product_id', $topProductIds)
                ->where('created_at', '>=', $start)
                ->select('product_id',
                    DB::raw("DATE_FORMAT(created_at, '%Y-%m') as ym"),
                    DB::raw('SUM(ABS(quantity)) as qty'))
                ->groupBy('product_id', 'ym')->get();

            $byProd = $rows->groupBy('product_id');
            foreach ($topProductIds as $pid) {
                $p = $productStock->firstWhere('id', $pid);
                if (! $p) continue;
                $data = [];
                for ($i = 5; $i >= 0; $i--) {
                    $m = Carbon::today()->subMonths($i);
                    $key = $m->format('Y-m');
                    $hit = ($byProd[$pid] ?? collect())->firstWhere('ym', $key);
                    $data[] = ['x' => $m->format('M'), 'y' => (int) ($hit->qty ?? 0)];
                }
                $movementMatrix[] = ['name' => $p->name, 'data' => $data];
            }
        }

        // Current stock vs reorder (top 15 low-stock items)
        $reorderCompare = $productStock
            ->filter(fn ($r) => (float) $r->reorder_level > 0)
            ->sortBy(fn ($r) => (float) $r->current_stock - (float) $r->reorder_level)
            ->take(15)->values()
            ->map(fn ($r) => [
                'name'    => $r->name,
                'current' => (int) $r->current_stock,
                'reorder' => (int) $r->reorder_level,
            ]);

        // Warehouse stock distribution
        $byWarehouse = DB::table('stock_movements as sm')
            ->leftJoin('warehouses as w', 'w.id', '=', 'sm.warehouse_id')
            ->select(DB::raw('COALESCE(w.name, "Unassigned") as name'),
                DB::raw("SUM(CASE WHEN sm.type IN ('in','adjustment') THEN sm.quantity ELSE -sm.quantity END) as qty"))
            ->groupBy('w.id', 'w.name')->orderByDesc('qty')->get();

        // Top moving products (last 30 days)
        $topMoving = DB::table('stock_movements as sm')
            ->join('products as p', 'p.id', '=', 'sm.product_id')
            ->where('sm.created_at', '>=', Carbon::today()->subDays(30))
            ->select('p.name', DB::raw('SUM(ABS(sm.quantity)) as qty'))
            ->groupBy('p.id', 'p.name')->orderByDesc('qty')->limit(8)->get();

        // Stock movement trend (in vs out, last 30 days)
        $movementTrend = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = Carbon::today()->subDays($i);
            $movementTrend[] = [
                'label' => $d->format('d M'),
                'in'    => (int) DB::table('stock_movements')->whereDate('created_at', $d)->whereIn('type', ['in', 'adjustment'])->sum('quantity'),
                'out'   => (int) DB::table('stock_movements')->whereDate('created_at', $d)->where('type', 'out')->sum('quantity'),
            ];
        }

        $lowStockList = $productStock
            ->filter(fn ($r) => (float) $r->reorder_level > 0 && (float) $r->current_stock <= (float) $r->reorder_level)
            ->sortBy('current_stock')->take(8)->values();

        return view('admin.dashboards.inventory', compact(
            'kpi', 'byCategory', 'movementMatrix', 'reorderCompare',
            'byWarehouse', 'topMoving', 'movementTrend', 'lowStockList'
        ));
    }

    // ════════════════════════════════════════════════════════════════════
    // 4. PURCHASE / VENDOR DASHBOARD
    // ════════════════════════════════════════════════════════════════════
    public function purchase()
    {
        abort_unless(Auth::guard('admin')->user()->can('purchase_orders.view') || Auth::guard('admin')->user()->can('vendors.view'), 403);

        $today = Carbon::today();
        $yearStart = $today->copy()->startOfYear();

        $kpi = [
            'po_total'       => PurchaseOrder::count(),
            'po_pending'     => PurchaseOrder::whereIn('status', ['draft', 'pending', 'confirmed'])->count(),
            'po_received'    => PurchaseOrder::where('status', 'received')->count(),
            'po_cancelled'   => PurchaseOrder::where('status', 'cancelled')->count(),
            'po_value_ytd'   => (float) PurchaseOrder::whereNotIn('status', ['cancelled'])
                                    ->where('po_date', '>=', $yearStart)->sum('grand_total'),
            'vendors_active' => Vendor::where('status', 'active')->count(),
        ];

        // Status flow (for sankey-style stacked bar)
        $flow = PurchaseOrder::select('status', DB::raw('COUNT(*) as cnt'), DB::raw('SUM(grand_total) as total'))
            ->groupBy('status')->get();

        // Top vendors by spend
        $topVendors = Vendor::withSum(['purchaseOrders as po_value' => function ($q) {
                $q->whereNotIn('status', ['cancelled']);
            }], 'grand_total')
            ->withCount(['purchaseOrders as po_count' => function ($q) {
                $q->whereNotIn('status', ['cancelled']);
            }])
            ->orderByDesc('po_value')->limit(10)->get();

        // Vendor concentration: top 5 vs rest
        $totalSpend = (float) PurchaseOrder::whereNotIn('status', ['cancelled'])->sum('grand_total');
        $top5Spend = $topVendors->take(5)->sum('po_value');
        $concentration = [
            'top5'  => $top5Spend,
            'rest'  => max(0, $totalSpend - $top5Spend),
        ];

        // Monthly PO spend (last 12 months)
        $spendTrend = [];
        for ($i = 11; $i >= 0; $i--) {
            $m = Carbon::today()->subMonths($i);
            $spendTrend[] = [
                'label' => $m->format('M Y'),
                'value' => (float) PurchaseOrder::whereNotIn('status', ['cancelled'])
                    ->whereYear('po_date', $m->year)->whereMonth('po_date', $m->month)->sum('grand_total'),
            ];
        }

        // PO aging (days since po_date for pending POs)
        $poAging = ['0-15' => 0, '16-30' => 0, '31-60' => 0, '60+' => 0];
        PurchaseOrder::whereIn('status', ['draft', 'pending', 'confirmed'])
            ->select('po_date')->chunk(500, function ($c) use (&$poAging) {
                foreach ($c as $po) {
                    $d = Carbon::parse($po->po_date)->diffInDays(now());
                    if ($d <= 15) $poAging['0-15']++;
                    elseif ($d <= 30) $poAging['16-30']++;
                    elseif ($d <= 60) $poAging['31-60']++;
                    else $poAging['60+']++;
                }
            });

        // Vendor delivery performance (avg days expected_date vs received date from GRN)
        $vendorPerf = DB::table('vendors as v')
            ->join('purchase_orders as po', 'po.vendor_id', '=', 'v.id')
            ->join('goods_receipts as gr', 'gr.purchase_order_id', '=', 'po.id')
            ->whereNotNull('po.expected_date')
            ->select('v.name',
                DB::raw('COUNT(DISTINCT po.id) as orders'),
                DB::raw('AVG(DATEDIFF(gr.received_date, po.expected_date)) as avg_delay'))
            ->groupBy('v.id', 'v.name')->orderByDesc('orders')->limit(8)->get();

        // Recent POs
        $recentPos = PurchaseOrder::with('vendor')->latest('po_date')->limit(6)->get();

        return view('admin.dashboards.purchase', compact(
            'kpi', 'flow', 'topVendors', 'concentration', 'totalSpend',
            'spendTrend', 'poAging', 'vendorPerf', 'recentPos'
        ));
    }

    // ════════════════════════════════════════════════════════════════════
    // 5. CUSTOMER ANALYTICS DASHBOARD
    // ════════════════════════════════════════════════════════════════════
    public function customers()
    {
        abort_unless(Auth::guard('admin')->user()->can('customers.view'), 403);

        $today = Carbon::today();
        $sixMonthsAgo = $today->copy()->subMonths(6);

        // KPI
        $kpi = [
            'total'        => Customer::count(),
            'active'       => Customer::where('status', 'active')->count(),
            'inactive'     => Customer::where('status', 'inactive')->count(),
            'new_mtd'      => Customer::where('created_at', '>=', $today->copy()->startOfMonth())->count(),
            'with_orders'  => Customer::has('invoices')->count(),
            'churn_risk'   => Customer::whereDoesntHave('invoices', fn ($q) => $q->where('invoice_date', '>=', $sixMonthsAgo))->count(),
        ];

        $totalRevenue = (float) Invoice::sum('grand_total');
        $kpi['total_revenue'] = $totalRevenue;

        // Top customers by revenue
        $topByRevenue = Customer::withSum('invoices', 'grand_total')
            ->withCount('invoices')
            ->orderByDesc('invoices_sum_grand_total')->limit(15)->get();

        // Concentration: top 10 vs rest
        $top10Rev = $topByRevenue->take(10)->sum('invoices_sum_grand_total');
        $concentration = [
            'top10' => $top10Rev,
            'rest'  => max(0, $totalRevenue - $top10Rev),
        ];

        // State distribution
        $byState = Customer::select('state', DB::raw('COUNT(*) as cnt'))
            ->whereNotNull('state')->where('state', '!=', '')
            ->groupBy('state')->orderByDesc('cnt')->limit(10)->get();

        // Acquisition trend (last 12 months)
        $acquisition = [];
        for ($i = 11; $i >= 0; $i--) {
            $m = Carbon::today()->subMonths($i);
            $acquisition[] = [
                'label' => $m->format('M Y'),
                'value' => Customer::whereYear('created_at', $m->year)->whereMonth('created_at', $m->month)->count(),
            ];
        }

        // Customer purchase-frequency segments (count + total spend per bucket)
        $segRows = DB::table('customers as c')
            ->leftJoin('invoices as i', 'i.customer_id', '=', 'c.id')
            ->select('c.id',
                DB::raw('COUNT(i.id) as orders'),
                DB::raw('COALESCE(SUM(i.grand_total), 0) as total'))
            ->groupBy('c.id')
            ->having('orders', '>', 0)->get();

        $freqSegments = [
            ['key' => 'one_time',   'label' => 'One-time (1 order)',    'min' => 1,  'max' => 1,            'count' => 0, 'revenue' => 0.0],
            ['key' => 'occasional', 'label' => 'Occasional (2–4)',      'min' => 2,  'max' => 4,            'count' => 0, 'revenue' => 0.0],
            ['key' => 'repeat',     'label' => 'Repeat (5–9)',          'min' => 5,  'max' => 9,            'count' => 0, 'revenue' => 0.0],
            ['key' => 'frequent',   'label' => 'Frequent (10+)',        'min' => 10, 'max' => PHP_INT_MAX,  'count' => 0, 'revenue' => 0.0],
        ];

        foreach ($segRows as $r) {
            $orders = (int) $r->orders;
            foreach ($freqSegments as &$s) {
                if ($orders >= $s['min'] && $orders <= $s['max']) {
                    $s['count']++;
                    $s['revenue'] += (float) $r->total;
                    break;
                }
            }
            unset($s);
        }

        $segBubble = array_map(fn ($s) => [
            'label' => $s['label'],
            'count' => $s['count'],
            'revenue' => round($s['revenue'], 2),
        ], $freqSegments);

        // Receivables aging heatmap (top 10 customers × aging buckets)
        $agingRows = DB::table('invoices as i')
            ->join('customers as c', 'c.id', '=', 'i.customer_id')
            ->whereIn('i.status', ['unpaid', 'partial', 'overdue'])
            ->select('c.id', 'c.name', 'i.invoice_date', 'i.balance_due')
            ->get();

        $byCust = $agingRows->groupBy('id');
        $custAging = [];
        foreach ($byCust as $cid => $rows) {
            $buckets = ['0-30' => 0, '31-60' => 0, '61-90' => 0, '90+' => 0];
            foreach ($rows as $r) {
                $d = Carbon::parse($r->invoice_date)->diffInDays(now());
                $amt = (float) $r->balance_due;
                if ($d <= 30) $buckets['0-30'] += $amt;
                elseif ($d <= 60) $buckets['31-60'] += $amt;
                elseif ($d <= 90) $buckets['61-90'] += $amt;
                else $buckets['90+'] += $amt;
            }
            $custAging[] = [
                'name'    => $rows->first()->name,
                'buckets' => $buckets,
                'total'   => array_sum($buckets),
            ];
        }
        usort($custAging, fn ($a, $b) => $b['total'] <=> $a['total']);
        $custAging = array_slice($custAging, 0, 10);

        // Customer loyalty tiers: bucket customers by months since first invoice
        $tenureRows = DB::table('customers as c')
            ->join('invoices as i', 'i.customer_id', '=', 'c.id')
            ->select('c.id',
                DB::raw('MIN(i.invoice_date) as first_inv'),
                DB::raw('SUM(i.grand_total) as total'))
            ->groupBy('c.id')->get();

        $tiers = [
            ['key' => 'new',     'label' => 'New (0–3 mo)',        'min' => 0,  'max' => 3,   'count' => 0, 'revenue' => 0.0],
            ['key' => 'growing', 'label' => 'Growing (4–12 mo)',   'min' => 4,  'max' => 12,  'count' => 0, 'revenue' => 0.0],
            ['key' => 'loyal',   'label' => 'Loyal (13–24 mo)',    'min' => 13, 'max' => 24,  'count' => 0, 'revenue' => 0.0],
            ['key' => 'vip',     'label' => 'VIP (24+ mo)',        'min' => 25, 'max' => PHP_INT_MAX, 'count' => 0, 'revenue' => 0.0],
        ];

        foreach ($tenureRows as $r) {
            $months = (int) floor(Carbon::parse($r->first_inv)->diffInMonths(now()));
            foreach ($tiers as &$t) {
                if ($months >= $t['min'] && $months <= $t['max']) {
                    $t['count']++;
                    $t['revenue'] += (float) $r->total;
                    break;
                }
            }
            unset($t);
        }

        $loyaltyTiers = array_map(fn ($t) => [
            'label' => $t['label'],
            'count' => $t['count'],
            'revenue' => round($t['revenue'], 2),
        ], $tiers);

        // Recent customers
        $recent = Customer::latest()->limit(6)->get();

        return view('admin.dashboards.customers', compact(
            'kpi', 'topByRevenue', 'concentration', 'totalRevenue',
            'byState', 'acquisition', 'segBubble', 'custAging', 'loyaltyTiers', 'recent'
        ));
    }

    // ════════════════════════════════════════════════════════════════════
    // 6. EXECUTIVE / FINANCE DASHBOARD
    // ════════════════════════════════════════════════════════════════════
    public function executive()
    {
        abort_unless(Auth::guard('admin')->user()->can('reports.view') || Auth::guard('admin')->user()->can('invoices.view'), 403);

        $today = Carbon::today();
        $monthStart = $today->copy()->startOfMonth();
        $yearStart = $today->copy()->startOfYear();

        // KPI
        $revMtd = (float) Invoice::where('invoice_date', '>=', $monthStart)->sum('grand_total');
        $revYtd = (float) Invoice::where('invoice_date', '>=', $yearStart)->sum('grand_total');
        $poMtd  = (float) PurchaseOrder::whereNotIn('status', ['cancelled'])
                    ->where('po_date', '>=', $monthStart)->sum('grand_total');
        $payMtd = (float) Payment::where('payment_date', '>=', $monthStart)->sum('amount');
        $receivables = (float) Invoice::whereIn('status', ['unpaid', 'partial', 'overdue'])->sum('balance_due');
        $overdueAmount = (float) Invoice::where(function ($q) {
                $q->where('status', 'overdue')
                    ->orWhere(function ($qq) { $qq->whereIn('status', ['unpaid', 'partial'])->where('due_date', '<', now()); });
            })->sum('balance_due');

        $kpi = [
            'rev_mtd'       => $revMtd,
            'rev_ytd'       => $revYtd,
            'po_mtd'        => $poMtd,
            'pay_mtd'       => $payMtd,
            'receivables'   => $receivables,
            'overdue'       => $overdueAmount,
            'gross_margin'  => $revMtd - $poMtd,
            'net_cash_flow' => $payMtd - $poMtd,
        ];

        // Revenue vs target (this month) — target = 110% of last month
        $lastMonthRev = (float) Invoice::whereYear('invoice_date', $today->copy()->subMonth()->year)
            ->whereMonth('invoice_date', $today->copy()->subMonth()->month)
            ->sum('grand_total');
        $target = $lastMonthRev * 1.1;
        $targetPct = $target > 0 ? min(100, round(($revMtd / $target) * 100, 1)) : 0;

        // Cash flow dual-line (last 12 months: invoiced vs paid vs po)
        $cashFlow = [];
        for ($i = 11; $i >= 0; $i--) {
            $m = Carbon::today()->subMonths($i);
            $cashFlow[] = [
                'label'    => $m->format('M Y'),
                'invoiced' => (float) Invoice::whereYear('invoice_date', $m->year)->whereMonth('invoice_date', $m->month)->sum('grand_total'),
                'paid'     => (float) Payment::whereYear('payment_date', $m->year)->whereMonth('payment_date', $m->month)->sum('amount'),
                'po'       => (float) PurchaseOrder::whereNotIn('status', ['cancelled'])
                                ->whereYear('po_date', $m->year)->whereMonth('po_date', $m->month)->sum('grand_total'),
            ];
        }

        // Working capital: receivables - payables over time
        $workingCap = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = Carbon::today()->subMonths($i)->endOfMonth();
            $recv = (float) Invoice::where('invoice_date', '<=', $m)
                ->whereIn('status', ['unpaid', 'partial', 'overdue'])->sum('balance_due');
            // Payables approximation: PO value not received yet as of month end
            $pay = (float) PurchaseOrder::where('po_date', '<=', $m)
                ->whereIn('status', ['draft', 'pending', 'confirmed'])->sum('grand_total');
            $workingCap[] = [
                'label'       => $m->format('M Y'),
                'receivables' => $recv,
                'payables'    => $pay,
            ];
        }

        // Receivables aging stacked
        $aging = ['0-30' => 0, '31-60' => 0, '61-90' => 0, '90+' => 0];
        Invoice::whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->select('invoice_date', 'balance_due')->chunk(500, function ($c) use (&$aging) {
                foreach ($c as $inv) {
                    $d = Carbon::parse($inv->invoice_date)->diffInDays(now());
                    $amt = (float) $inv->balance_due;
                    if ($d <= 30) $aging['0-30'] += $amt;
                    elseif ($d <= 60) $aging['31-60'] += $amt;
                    elseif ($d <= 90) $aging['61-90'] += $amt;
                    else $aging['90+'] += $amt;
                }
            });

        // Payment mode distribution (last 6 months)
        $paymentModes = Payment::where('payment_date', '>=', $today->copy()->subMonths(6))
            ->select('mode', DB::raw('SUM(amount) as total'))
            ->groupBy('mode')->pluck('total', 'mode')->toArray();

        // Margin trend (revenue - po cost, last 6 months)
        $marginTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = Carbon::today()->subMonths($i);
            $rev = (float) Invoice::whereYear('invoice_date', $m->year)->whereMonth('invoice_date', $m->month)->sum('grand_total');
            $cost = (float) PurchaseOrder::whereNotIn('status', ['cancelled'])
                ->whereYear('po_date', $m->year)->whereMonth('po_date', $m->month)->sum('grand_total');
            $marginTrend[] = [
                'label'   => $m->format('M Y'),
                'revenue' => $rev,
                'cost'    => $cost,
                'margin'  => $rev - $cost,
                'pct'     => $rev > 0 ? round((($rev - $cost) / $rev) * 100, 1) : 0,
            ];
        }

        // Top products by margin (rough: selling_price - purchase_price × units sold)
        $topMargin = DB::table('sales_order_items as si')
            ->join('products as p', 'p.id', '=', 'si.product_id')
            ->select('p.name',
                DB::raw('SUM(si.quantity) as qty'),
                DB::raw('SUM(si.quantity * (p.selling_price - p.purchase_price)) as margin'))
            ->groupBy('p.id', 'p.name')
            ->orderByDesc('margin')->limit(10)->get();

        return view('admin.dashboards.executive', compact(
            'kpi', 'target', 'targetPct', 'cashFlow', 'workingCap',
            'aging', 'paymentModes', 'marginTrend', 'topMargin'
        ));
    }
}
