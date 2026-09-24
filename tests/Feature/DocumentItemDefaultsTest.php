<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\QuotationItem;
use App\Models\Vendor;
use App\Services\InvoiceService;
use App\Services\ProformaInvoiceService;
use App\Services\PurchaseOrderService;
use App\Services\SalesOrderService;
use App\Support\Tenancy\CurrentBusiness;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUsers;

/**
 * Regression: item columns (tax_percent, discount_percent, quantity, rate) are
 * NOT NULL with a 0 default, but blank form fields arrive as explicit null
 * (ConvertEmptyStringsToNull) and pass the 'nullable' validation rule — the
 * insert then dies with "Column 'tax_percent' cannot be null" (SQLSTATE 23000).
 * normalizeItems() in every document service must write coerced numerics back.
 */
class DocumentItemDefaultsTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    protected Business $business;

    protected Admin $admin;

    protected Customer $customer;

    protected Vendor $vendor;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();

        $this->business = Business::create([
            'name' => 'Test Co', 'slug' => 'test-co',
            'currency_code' => 'INR', 'currency_symbol' => '₹',
        ]);
        app(CurrentBusiness::class)->set($this->business);

        $this->admin = Admin::create([
            'name' => 'Admin', 'email' => 'admin@test.com',
            'password' => bcrypt('password'), 'phone' => '9990001111',
            'status' => 'active', 'business_id' => $this->business->id,
        ]);
        $this->admin->assignRole('Admin');

        $this->customer = Customer::create([
            'business_id' => $this->business->id,
            'code' => 'CUST-0001', 'name' => 'Test Customer', 'status' => 'active',
        ]);

        $this->vendor = Vendor::create([
            'business_id' => $this->business->id,
            'code' => 'VEND-0001', 'name' => 'Test Vendor', 'status' => 'active',
        ]);

        $category = ProductCategory::create([
            'business_id' => $this->business->id,
            'name' => 'Cat', 'slug' => 'cat', 'is_active' => true,
        ]);

        $this->product = Product::create([
            'business_id' => $this->business->id,
            'code' => 'PRD-0001', 'name' => 'Kit', 'category_id' => $category->id,
            'unit' => 'pcs', 'purchase_price' => 100, 'selling_price' => 150,
            'tax_percent' => 18, 'status' => 'active',
        ]);
    }

    /** An item row exactly as the browser sends it with tax/discount left blank. */
    private function blankTaxItem(): array
    {
        return [
            'product_id' => $this->product->id,
            'description' => 'Kit with blank tax field',
            'hsn_code' => null,
            'quantity' => 1,
            'unit' => 'pcs',
            'rate' => 150,
            'discount_percent' => null,
            'tax_percent' => null,
        ];
    }

    #[Test]
    public function quotation_store_with_blank_item_tax_does_not_500(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.quotations.store'), [
                'customer_id' => $this->customer->id,
                'quotation_date' => now()->toDateString(),
                'status' => 'draft',
                'discount_type' => 'percent',
                'discount_value' => 0,
                'tax_percent' => 18,
                // Blank selects/inputs reach PHP as empty strings; the
                // ConvertEmptyStringsToNull middleware turns them into null —
                // the exact payload that crashed in production.
                'items' => [[
                    'product_id' => (string) $this->product->id,
                    'description' => 'Kit with blank tax field',
                    'hsn_code' => '',
                    'quantity' => '1',
                    'unit' => 'pcs',
                    'rate' => '150',
                    'discount_percent' => '',
                    'tax_percent' => '',
                ]],
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $item = QuotationItem::firstOrFail();
        $this->assertSame('0.00', (string) $item->tax_percent);
        $this->assertSame('0.00', (string) $item->discount_percent);
    }

    #[Test]
    public function invoice_service_defaults_blank_item_numerics(): void
    {
        $this->actingAs($this->admin, 'admin');

        $invoice = app(InvoiceService::class)->create([
            'customer_id' => $this->customer->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
        ], [$this->blankTaxItem()]);

        $this->assertSame('0.00', (string) $invoice->items->first()->tax_percent);
    }

    #[Test]
    public function proforma_service_defaults_blank_item_numerics(): void
    {
        $this->actingAs($this->admin, 'admin');

        $proforma = app(ProformaInvoiceService::class)->create([
            'customer_id' => $this->customer->id,
            'proforma_date' => now()->toDateString(),
        ], [$this->blankTaxItem()]);

        $this->assertSame('0.00', (string) $proforma->items->first()->tax_percent);
    }

    #[Test]
    public function sales_order_service_defaults_blank_item_numerics(): void
    {
        $this->actingAs($this->admin, 'admin');

        $order = app(SalesOrderService::class)->create([
            'customer_id' => $this->customer->id,
            'order_date' => now()->toDateString(),
        ], [$this->blankTaxItem()]);

        $this->assertSame('0.00', (string) $order->items->first()->tax_percent);
    }

    #[Test]
    public function purchase_order_service_defaults_blank_item_numerics(): void
    {
        $this->actingAs($this->admin, 'admin');

        $po = app(PurchaseOrderService::class)->create([
            'vendor_id' => $this->vendor->id,
            'po_date' => now()->toDateString(),
        ], [$this->blankTaxItem()]);

        $this->assertSame('0.00', (string) $po->items->first()->tax_percent);
    }
}
