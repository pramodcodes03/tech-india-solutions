<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUsers;

class InvoiceTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    protected Admin $accountsUser;

    protected Customer $customer;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();
        $this->accountsUser = $this->createAdminWithRole('Accounts', 'accounts@test.com');

        $this->customer = Customer::create([
            'code' => 'CUST-0001',
            'name' => 'Test Customer',
            'status' => 'active',
            'created_by' => $this->accountsUser->id,
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
            'created_by' => $this->accountsUser->id,
        ]);
    }

    #[Test]
    public function test_accounts_user_can_view_invoices(): void
    {
        $response = $this->actingAs($this->accountsUser, 'admin')
            ->get(route('admin.invoices.index'));

        $response->assertStatus(200);
    }

    #[Test]
    public function test_accounts_user_can_create_invoice(): void
    {
        $response = $this->actingAs($this->accountsUser, 'admin')
            ->post(route('admin.invoices.store'), [
                'customer_id' => $this->customer->id,
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
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

        $response->assertRedirect(route('admin.invoices.index'));

        $invoice = Invoice::first();
        $this->assertNotNull($invoice);
        $this->assertMatchesRegularExpression('/^INV-\d{4}-\d{4}$/', $invoice->invoice_number);
        $this->assertEquals('unpaid', $invoice->status);
        $this->assertEquals('1500.00', $invoice->subtotal);
    }

    #[Test]
    public function test_invoice_balance_recalculates_on_payment(): void
    {
        $invoice = Invoice::create([
            'invoice_number' => 'INV-2026-0001',
            'customer_id' => $this->customer->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'subtotal' => 1000,
            'discount_type' => 'fixed',
            'discount_value' => 0,
            'tax_percent' => 0,
            'tax_amount' => 0,
            'grand_total' => 1000,
            'amount_paid' => 0,
            'balance_due' => 1000,
            'status' => 'unpaid',
            'created_by' => $this->accountsUser->id,
        ]);

        // Record a partial payment
        $this->actingAs($this->accountsUser, 'admin')
            ->post(route('admin.payments.store'), [
                'invoice_id' => $invoice->id,
                'payment_date' => now()->toDateString(),
                'amount' => 400,
                'mode' => 'bank_transfer',
                'reference_no' => 'REF-001',
            ]);

        $invoice->refresh();
        $this->assertEquals('400.00', $invoice->amount_paid);
        $this->assertEquals('600.00', $invoice->balance_due);
        $this->assertEquals('partial', $invoice->status);
    }

    #[Test]
    public function test_invoice_pdf_returns_pdf_response(): void
    {
        $invoice = Invoice::create([
            'invoice_number' => 'INV-2026-0001',
            'customer_id' => $this->customer->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'subtotal' => 1500,
            'discount_type' => 'fixed',
            'discount_value' => 0,
            'tax_percent' => 18,
            'tax_amount' => 270,
            'grand_total' => 1770,
            'amount_paid' => 0,
            'balance_due' => 1770,
            'status' => 'unpaid',
            'created_by' => $this->accountsUser->id,
        ]);

        $response = $this->actingAs($this->accountsUser, 'admin')
            ->get(route('admin.invoices.pdf', $invoice->id));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }
}
