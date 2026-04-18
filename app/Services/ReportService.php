<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Collection;

class ReportService
{
    /**
     * Sales report filtered by date range, customer, and/or product.
     */
    public function salesReport(array $filters): Collection
    {
        $query = Invoice::query()
            ->with(['customer', 'items.product'])
            ->whereNull('deleted_at');

        if (! empty($filters['date_from'])) {
            $query->where('invoice_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('invoice_date', '<=', $filters['date_to']);
        }

        if (! empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (! empty($filters['product_id'])) {
            $query->whereHas('items', function ($q) use ($filters) {
                $q->where('product_id', $filters['product_id']);
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderByDesc('invoice_date')->get();
    }

    /**
     * Inventory report: current stock levels and recent movements.
     */
    public function inventoryReport(array $filters): Collection
    {
        $query = Product::query()
            ->with(['category', 'stockMovements'])
            ->whereNull('deleted_at');

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (! empty($filters['warehouse_id'])) {
            $query->whereHas('stockMovements', function ($q) use ($filters) {
                $q->where('warehouse_id', $filters['warehouse_id']);
            });
        }

        if (! empty($filters['low_stock'])) {
            $query->get()->filter(function ($product) {
                return $product->current_stock <= $product->reorder_level;
            });
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Customer report: outstanding balances and summaries.
     */
    public function customerReport(array $filters): Collection
    {
        $query = Customer::query()
            ->with('invoices')
            ->withSum('invoices as total_invoiced', 'grand_total')
            ->withSum('payments as total_paid', 'amount')
            ->whereNull('deleted_at');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['city'])) {
            $query->where('city', $filters['city']);
        }

        return $query->orderBy('name')->get()->map(function ($customer) {
            $customer->balance_due = ($customer->total_invoiced ?? 0) - ($customer->total_paid ?? 0);

            return $customer;
        });
    }

    /**
     * Purchase report: purchase orders with optional filters.
     */
    public function purchaseReport(array $filters): Collection
    {
        $query = PurchaseOrder::query()
            ->with(['vendor', 'items.product'])
            ->whereNull('deleted_at');

        if (! empty($filters['date_from'])) {
            $query->where('po_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('po_date', '<=', $filters['date_to']);
        }

        if (! empty($filters['vendor_id'])) {
            $query->where('vendor_id', $filters['vendor_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderByDesc('po_date')->get();
    }

    /**
     * Payment report: payments with optional filters.
     */
    public function paymentReport(array $filters): Collection
    {
        $query = Payment::query()
            ->with(['invoice', 'customer'])
            ->whereNull('deleted_at');

        if (! empty($filters['date_from'])) {
            $query->where('payment_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('payment_date', '<=', $filters['date_to']);
        }

        if (! empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (! empty($filters['mode'])) {
            $query->where('mode', $filters['mode']);
        }

        return $query->orderByDesc('payment_date')->get();
    }
}
