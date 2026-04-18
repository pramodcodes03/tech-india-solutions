<?php

namespace App\Http\Controllers\Admin;

use App\Exports\CustomerReportExport;
use App\Exports\InventoryReportExport;
use App\Exports\PaymentReportExport;
use App\Exports\PurchaseReportExport;
use App\Exports\SalesReportExport;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function __construct(
        protected ReportService $reportService,
    ) {}

    public function index()
    {
        abort_unless(Auth::guard('admin')->user()->can('reports.view'), 403);

        return view('admin.reports.index');
    }

    public function sales(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('reports.view'), 403);

        $filters = $request->only(['date_from', 'date_to', 'customer_id', 'product_id', 'status']);
        $data = $this->reportService->salesReport($filters);
        $customers = Customer::where('status', 'active')->orderBy('name')->get();
        $products = Product::where('status', 'active')->orderBy('name')->get();

        // Flatten invoice → line items for the table
        $results = collect();
        foreach ($data as $invoice) {
            foreach ($invoice->items as $item) {
                $results->push((object) [
                    'date'          => $invoice->invoice_date?->format('d M Y') ?? '-',
                    'invoice_number'=> $invoice->invoice_number,
                    'customer_name' => $invoice->customer->name ?? '-',
                    'product_name'  => $item->product->name ?? $item->description ?? '-',
                    'quantity'      => $item->quantity,
                    'rate'          => $item->rate,
                    'amount'        => $item->line_total,
                    'status'        => $invoice->status,
                ]);
            }
        }

        $summary = [
            'total_sales'     => $data->sum('grand_total'),
            'total_invoices'  => $data->count(),
            'avg_order_value' => $data->count() > 0 ? $data->sum('grand_total') / $data->count() : 0,
        ];

        return view('admin.reports.sales', compact('results', 'summary', 'filters', 'customers', 'products'));
    }

    public function inventory(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('reports.view'), 403);

        $filters = $request->only(['category_id', 'warehouse_id', 'low_stock']);
        $data = $this->reportService->inventoryReport($filters);
        $products = Product::where('status', 'active')->orderBy('name')->get();
        $categories = ProductCategory::orderBy('name')->get();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        $results = $data->map(fn ($p) => (object) [
            'product_code'  => $p->code ?? '-',
            'product_name'  => $p->name,
            'category_name' => $p->category->name ?? '-',
            'warehouse_name'=> '-',
            'current_stock' => $p->current_stock,
            'reorder_level' => $p->reorder_level ?? 0,
        ]);

        return view('admin.reports.inventory', compact('results', 'filters', 'products', 'categories', 'warehouses'));
    }

    public function customers(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('reports.view'), 403);

        $filters = $request->only(['status', 'city', 'customer_id', 'date_from', 'date_to']);
        $data = $this->reportService->customerReport($filters);
        $customers = Customer::where('status', 'active')->orderBy('name')->get();

        $results = $data->map(fn ($c) => (object) [
            'customer_name'  => $c->name,
            'total_orders'   => $c->invoices->count(),
            'total_invoiced' => $c->total_invoiced ?? 0,
            'total_paid'     => $c->total_paid ?? 0,
        ]);

        return view('admin.reports.customers', compact('results', 'filters', 'customers'));
    }

    public function purchases(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('reports.view'), 403);

        $filters = $request->only(['date_from', 'date_to', 'vendor_id', 'status']);
        $data = $this->reportService->purchaseReport($filters);
        $vendors = Vendor::where('status', 'active')->orderBy('name')->get();

        $results = $data->map(fn ($po) => (object) [
            'po_number'   => $po->po_number,
            'vendor_name' => $po->vendor->name ?? '-',
            'date'        => $po->po_date?->format('d M Y') ?? '-',
            'grand_total' => $po->grand_total,
            'status'      => $po->status,
            'received'    => $po->status === 'received' ? $po->updated_at?->format('d M Y') : null,
        ]);

        return view('admin.reports.purchases', compact('results', 'filters', 'vendors'));
    }

    public function payments(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('reports.view'), 403);

        $filters = $request->only(['date_from', 'date_to', 'customer_id', 'mode']);
        $data = $this->reportService->paymentReport($filters);
        $customers = Customer::where('status', 'active')->orderBy('name')->get();

        $results = $data->map(fn ($p) => (object) [
            'payment_number'   => $p->payment_number,
            'date'             => $p->payment_date?->format('d M Y') ?? '-',
            'customer_name'    => $p->customer->name ?? '-',
            'invoice_number'   => $p->invoice->invoice_number ?? '-',
            'amount'           => $p->amount,
            'mode'             => $p->mode ?? '-',
            'reference_number' => $p->reference_no ?? '-',
        ]);

        return view('admin.reports.payments', compact('results', 'filters', 'customers'));
    }

    public function exportExcel(Request $request, string $type)
    {
        abort_unless(Auth::guard('admin')->user()->can('reports.export'), 403);

        $filters = $request->all();
        $data = $this->getReportData($type, $filters);
        $exportClass = $this->resolveExportClass($type, $data);

        $filename = "{$type}-report-".now()->format('Y-m-d').'.xlsx';

        return Excel::download($exportClass, $filename);
    }

    public function exportPdf(Request $request, string $type)
    {
        abort_unless(Auth::guard('admin')->user()->can('reports.export'), 403);

        $filters = $request->all();
        $data = $this->getReportData($type, $filters);

        $pdf = Pdf::loadView("admin.reports.pdf.{$type}", compact('data', 'filters'));

        $filename = "{$type}-report-".now()->format('Y-m-d').'.pdf';

        return $pdf->stream($filename);
    }

    protected function getReportData(string $type, array $filters)
    {
        return match ($type) {
            'sales' => $this->reportService->salesReport($filters),
            'inventory' => $this->reportService->inventoryReport($filters),
            'customers' => $this->reportService->customerReport($filters),
            'purchases' => $this->reportService->purchaseReport($filters),
            'payments' => $this->reportService->paymentReport($filters),
            default => abort(404, 'Unknown report type.'),
        };
    }

    protected function resolveExportClass(string $type, $data)
    {
        $exportClasses = [
            'sales' => SalesReportExport::class,
            'inventory' => InventoryReportExport::class,
            'customers' => CustomerReportExport::class,
            'purchases' => PurchaseReportExport::class,
            'payments' => PaymentReportExport::class,
        ];

        $class = $exportClasses[$type] ?? abort(404, 'Unknown report type.');

        return new $class($data);
    }
}
