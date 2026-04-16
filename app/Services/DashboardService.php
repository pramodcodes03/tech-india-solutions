<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Product;
use App\Models\ServiceTicket;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function __construct(
        protected InventoryService $inventoryService,
    ) {}

    /**
     * Get key dashboard statistics.
     *
     * Returns total sales this month, total receivables,
     * low stock count, and open service tickets count.
     */
    public function getStats(): array
    {
        $now = Carbon::now();

        // Total sales this month (sum of grand_total for invoices created this month)
        $totalSalesThisMonth = Invoice::whereNull('deleted_at')
            ->whereYear('invoice_date', $now->year)
            ->whereMonth('invoice_date', $now->month)
            ->sum('grand_total');

        // Total receivables (sum of balance_due across all unpaid/partial invoices)
        $totalReceivables = Invoice::whereNull('deleted_at')
            ->whereIn('status', ['unpaid', 'partial'])
            ->sum('balance_due');

        // Low stock count
        $lowStockCount = $this->inventoryService->getLowStockProducts()->count();

        // Open service tickets count
        $openTicketsCount = ServiceTicket::whereNull('deleted_at')
            ->whereNotIn('status', ['closed', 'resolved'])
            ->count();

        return [
            'total_sales_this_month' => round((float) $totalSalesThisMonth, 2),
            'total_receivables' => round((float) $totalReceivables, 2),
            'low_stock_count' => $lowStockCount,
            'open_tickets_count' => $openTicketsCount,
        ];
    }

    /**
     * Get leads grouped by status for a pie chart.
     */
    public function getLeadsByStatus(): array
    {
        return Lead::whereNull('deleted_at')
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    /**
     * Get monthly sales trend for the last 12 months (for a line chart).
     */
    public function getSalesTrend(): array
    {
        $results = [];
        $now = Carbon::now();

        for ($i = 11; $i >= 0; $i--) {
            $date = $now->copy()->subMonths($i);
            $month = $date->format('Y-m');

            $total = Invoice::whereNull('deleted_at')
                ->whereYear('invoice_date', $date->year)
                ->whereMonth('invoice_date', $date->month)
                ->sum('grand_total');

            $results[] = [
                'month' => $month,
                'label' => $date->format('M Y'),
                'total' => round((float) $total, 2),
            ];
        }

        return $results;
    }

    /**
     * Get top customers by total invoiced amount.
     */
    public function getTopCustomers(int $limit = 5): Collection
    {
        return Customer::whereNull('deleted_at')
            ->withCount(['invoices' => function ($q) {
                $q->whereNull('deleted_at');
            }])
            ->withSum(['invoices' => function ($q) {
                $q->whereNull('deleted_at');
            }], 'grand_total')
            ->orderByDesc('invoices_sum_grand_total')
            ->limit($limit)
            ->get();
    }

    /**
     * Get top products by total quantity sold (from invoice items).
     */
    public function getTopProducts(int $limit = 5): Collection
    {
        return Product::whereNull('deleted_at')
            ->withSum(['salesOrderItems' => function ($q) {
                $q->whereHas('salesOrder', function ($sq) {
                    $sq->whereNull('deleted_at');
                });
            }], 'quantity')
            ->orderByDesc('sales_order_items_sum_quantity')
            ->limit($limit)
            ->get();
    }
}
