<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\SalesOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUsers;

class QuotationTest extends TestCase
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
    public function test_sales_user_can_view_quotations(): void
    {
        $response = $this->actingAs($this->salesUser, 'admin')
            ->get(route('admin.quotations.index'));

        $response->assertStatus(200);
    }

    #[Test]
    public function test_sales_user_can_create_quotation_with_items(): void
    {
        $response = $this->actingAs($this->salesUser, 'admin')
            ->post(route('admin.quotations.store'), [
                'customer_id' => $this->customer->id,
                'quotation_date' => now()->toDateString(),
                'valid_until' => now()->addDays(30)->toDateString(),
                'status' => 'draft',
                'discount_type' => 'fixed',
                'discount_value' => 0,
                'tax_percent' => 18,
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'description' => 'Test Product',
                        'hsn_code' => '1234',
                        'quantity' => 10,
                        'unit' => 'pcs',
                        'rate' => 150,
                        'line_total' => 1500,
                    ],
                ],
            ]);

        $response->assertRedirect(route('admin.quotations.index'));

        $quotation = Quotation::first();
        $this->assertNotNull($quotation);
        $this->assertMatchesRegularExpression('/^QUO-\d{4}-\d{4}$/', $quotation->quotation_number);
        $this->assertEquals(1, $quotation->items()->count());
    }

    #[Test]
    public function test_quotation_totals_are_calculated_correctly(): void
    {
        $this->actingAs($this->salesUser, 'admin')
            ->post(route('admin.quotations.store'), [
                'customer_id' => $this->customer->id,
                'quotation_date' => now()->toDateString(),
                'discount_type' => 'fixed',
                'discount_value' => 100,
                'tax_percent' => 18,
                'items' => [
                    [
                        'description' => 'Item 1',
                        'quantity' => 10,
                        'unit' => 'pcs',
                        'rate' => 100,
                        'line_total' => 1000,
                    ],
                    [
                        'description' => 'Item 2',
                        'quantity' => 5,
                        'unit' => 'pcs',
                        'rate' => 200,
                        'line_total' => 1000,
                    ],
                ],
            ]);

        $quotation = Quotation::first();
        $this->assertNotNull($quotation);

        // subtotal = 1000 + 1000 = 2000
        // discount = 100 (fixed)
        // after discount = 1900
        // tax = 1900 * 18% = 342
        // grand total = 1900 + 342 = 2242
        $this->assertEquals('2000.00', $quotation->subtotal);
        $this->assertEquals('342.00', $quotation->tax_amount);
        $this->assertEquals('2242.00', $quotation->grand_total);
    }

    #[Test]
    public function test_quotation_can_be_cloned(): void
    {
        $quotation = Quotation::create([
            'quotation_number' => 'QUO-2026-0001',
            'customer_id' => $this->customer->id,
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'status' => 'sent',
            'subtotal' => 1500,
            'discount_type' => 'fixed',
            'discount_value' => 0,
            'tax_percent' => 18,
            'tax_amount' => 270,
            'grand_total' => 1770,
            'created_by' => $this->salesUser->id,
        ]);

        QuotationItem::create([
            'quotation_id' => $quotation->id,
            'product_id' => $this->product->id,
            'description' => 'Test Product',
            'quantity' => 10,
            'unit' => 'pcs',
            'rate' => 150,
            'line_total' => 1500,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($this->salesUser, 'admin')
            ->post(route('admin.quotations.clone', $quotation->id));

        $response->assertRedirect();

        $this->assertEquals(2, Quotation::count());
        $cloned = Quotation::where('id', '!=', $quotation->id)->first();
        $this->assertEquals('draft', $cloned->status);
        $this->assertNotEquals($quotation->quotation_number, $cloned->quotation_number);
        $this->assertEquals(1, $cloned->items()->count());
    }

    #[Test]
    public function test_quotation_can_be_converted_to_sales_order(): void
    {
        $quotation = Quotation::create([
            'quotation_number' => 'QUO-2026-0001',
            'customer_id' => $this->customer->id,
            'quotation_date' => now()->toDateString(),
            'status' => 'draft',
            'subtotal' => 1500,
            'discount_type' => 'fixed',
            'discount_value' => 0,
            'tax_percent' => 18,
            'tax_amount' => 270,
            'grand_total' => 1770,
            'created_by' => $this->salesUser->id,
        ]);

        QuotationItem::create([
            'quotation_id' => $quotation->id,
            'product_id' => $this->product->id,
            'description' => 'Test Product',
            'quantity' => 10,
            'unit' => 'pcs',
            'rate' => 150,
            'line_total' => 1500,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($this->salesUser, 'admin')
            ->post(route('admin.quotations.convert', $quotation->id));

        $response->assertRedirect();

        $quotation->refresh();
        $this->assertEquals('accepted', $quotation->status);

        $salesOrder = SalesOrder::first();
        $this->assertNotNull($salesOrder);
        $this->assertEquals($quotation->id, $salesOrder->quotation_id);
        $this->assertEquals($this->customer->id, $salesOrder->customer_id);
    }

    #[Test]
    public function test_quotation_pdf_returns_pdf_response(): void
    {
        $quotation = Quotation::create([
            'quotation_number' => 'QUO-2026-0001',
            'customer_id' => $this->customer->id,
            'quotation_date' => now()->toDateString(),
            'status' => 'draft',
            'subtotal' => 1500,
            'discount_type' => 'fixed',
            'discount_value' => 0,
            'tax_percent' => 18,
            'tax_amount' => 270,
            'grand_total' => 1770,
            'created_by' => $this->salesUser->id,
        ]);

        $response = $this->actingAs($this->salesUser, 'admin')
            ->get(route('admin.quotations.pdf', $quotation->id));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }
}
