<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUsers;

class SalesOrderTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    protected Admin $salesUser;

    protected Customer $customer;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();
        $this->salesUser = $this->createAdminWithRole('Sales', 'sales@test.com');

        $this->customer = Customer::create([
            'code' => 'CUST-0001',
            'name' => 'Test Customer',
            'status' => 'active',
            'created_by' => $this->salesUser->id,
        ]);

        $category = ProductCategory::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'code' => 'PRD-0001',
            'name' => 'Test Product',
            'category_id' => $category->id,
            'unit' => 'pcs',
            'purchase_price' => 100,
            'selling_price' => 150,
            'tax_percent' => 18,
            'status' => 'active',
            'created_by' => $this->salesUser->id,
        ]);
    }

    #[Test]
    public function test_sales_user_can_view_sales_orders(): void
    {
        $response = $this->actingAs($this->salesUser, 'admin')
            ->get(route('admin.sales-orders.index'));

        $response->assertStatus(200);
    }

    #[Test]
    public function test_sales_user_can_create_sales_order(): void
    {
        $response = $this->actingAs($this->salesUser, 'admin')
            ->post(route('admin.sales-orders.store'), [
                'customer_id' => $this->customer->id,
                'order_date' => now()->toDateString(),
                'discount_type' => 'fixed',
                'discount_value' => 0,
                'tax_percent' => 18,
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'description' => 'Test Product',
                        'hsn_code' => '1234',
                        'quantity' => 5,
                        'unit' => 'pcs',
                        'rate' => 150,
                        'line_total' => 750,
                    ],
                ],
            ]);

        $response->assertRedirect(route('admin.sales-orders.index'));

        $salesOrder = SalesOrder::first();
        $this->assertNotNull($salesOrder);
        $this->assertMatchesRegularExpression('/^SO-\d{4}-\d{4}$/', $salesOrder->order_number);
        $this->assertEquals('pending', $salesOrder->status);
    }

    #[Test]
    public function test_sales_order_status_can_be_updated(): void
    {
        $warehouse = Warehouse::create([
            'code' => 'WH-001',
            'name' => 'Main Warehouse',
            'is_active' => true,
            'is_default' => true,
        ]);

        $salesOrder = SalesOrder::create([
            'order_number' => 'SO-2026-0001',
            'customer_id' => $this->customer->id,
            'order_date' => now()->toDateString(),
            'status' => 'pending',
            'subtotal' => 750,
            'discount_type' => 'fixed',
            'discount_value' => 0,
            'tax_percent' => 18,
            'tax_amount' => 135,
            'grand_total' => 885,
            'created_by' => $this->salesUser->id,
        ]);

        SalesOrderItem::create([
            'sales_order_id' => $salesOrder->id,
            'product_id' => $this->product->id,
            'description' => 'Test Product',
            'quantity' => 5,
            'unit' => 'pcs',
            'rate' => 150,
            'line_total' => 750,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($this->salesUser, 'admin')
            ->patch(route('admin.sales-orders.update-status', $salesOrder->id), [
                'status' => 'confirmed',
            ]);

        $response->assertRedirect();
        $salesOrder->refresh();
        $this->assertEquals('confirmed', $salesOrder->status);
    }

    #[Test]
    public function test_delivered_order_cannot_be_edited(): void
    {
        $salesOrder = SalesOrder::create([
            'order_number' => 'SO-2026-0001',
            'customer_id' => $this->customer->id,
            'order_date' => now()->toDateString(),
            'status' => 'delivered',
            'subtotal' => 750,
            'discount_type' => 'fixed',
            'discount_value' => 0,
            'tax_percent' => 18,
            'tax_amount' => 135,
            'grand_total' => 885,
            'created_by' => $this->salesUser->id,
        ]);

        $response = $this->actingAs($this->salesUser, 'admin')
            ->get(route('admin.sales-orders.edit', $salesOrder->id));

        // Controller redirects back with error for delivered orders
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    #[Test]
    public function test_invoice_can_be_generated_from_order(): void
    {
        $salesOrder = SalesOrder::create([
            'order_number' => 'SO-2026-0001',
            'customer_id' => $this->customer->id,
            'order_date' => now()->toDateString(),
            'status' => 'confirmed',
            'subtotal' => 750,
            'discount_type' => 'fixed',
            'discount_value' => 0,
            'tax_percent' => 18,
            'tax_amount' => 135,
            'grand_total' => 885,
            'created_by' => $this->salesUser->id,
        ]);

        SalesOrderItem::create([
            'sales_order_id' => $salesOrder->id,
            'product_id' => $this->product->id,
            'description' => 'Test Product',
            'quantity' => 5,
            'unit' => 'pcs',
            'rate' => 150,
            'line_total' => 750,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($this->salesUser, 'admin')
            ->post(route('admin.sales-orders.generate-invoice', $salesOrder->id));

        $response->assertRedirect();

        $invoice = Invoice::first();
        $this->assertNotNull($invoice);
        $this->assertEquals($salesOrder->id, $invoice->sales_order_id);
        $this->assertEquals($this->customer->id, $invoice->customer_id);
        $this->assertEquals('unpaid', $invoice->status);
        $this->assertMatchesRegularExpression('/^INV-\d{4}-\d{4}$/', $invoice->invoice_number);
    }
}
