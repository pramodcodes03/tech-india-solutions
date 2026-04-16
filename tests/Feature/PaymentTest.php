<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUsers;

class PaymentTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    protected Admin $accountsUser;

    protected Customer $customer;

    protected Invoice $invoice;

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

        $this->invoice = Invoice::create([
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
    }

    #[Test]
    public function test_accounts_user_can_record_payment(): void
    {
        $response = $this->actingAs($this->accountsUser, 'admin')
            ->post(route('admin.payments.store'), [
                'invoice_id' => $this->invoice->id,
                'payment_date' => now()->toDateString(),
                'amount' => 500,
                'mode' => 'cash',
                'reference_no' => 'CASH-001',
                'notes' => 'Partial payment',
            ]);

        $response->assertRedirect(route('admin.payments.index'));

        $payment = Payment::first();
        $this->assertNotNull($payment);
        $this->assertMatchesRegularExpression('/^PAY-\d{4}-\d{4}$/', $payment->payment_number);
        $this->assertEquals('500.00', $payment->amount);
        $this->assertEquals($this->invoice->id, $payment->invoice_id);
        $this->assertEquals($this->customer->id, $payment->customer_id);
    }

    #[Test]
    public function test_payment_updates_invoice_status(): void
    {
        $this->actingAs($this->accountsUser, 'admin')
            ->post(route('admin.payments.store'), [
                'invoice_id' => $this->invoice->id,
                'payment_date' => now()->toDateString(),
                'amount' => 300,
                'mode' => 'upi',
            ]);

        $this->invoice->refresh();
        $this->assertEquals('300.00', $this->invoice->amount_paid);
        $this->assertEquals('700.00', $this->invoice->balance_due);
        $this->assertEquals('partial', $this->invoice->status);
    }

    #[Test]
    public function test_full_payment_marks_invoice_as_paid(): void
    {
        $this->actingAs($this->accountsUser, 'admin')
            ->post(route('admin.payments.store'), [
                'invoice_id' => $this->invoice->id,
                'payment_date' => now()->toDateString(),
                'amount' => 1000,
                'mode' => 'bank_transfer',
                'reference_no' => 'TXN-12345',
            ]);

        $this->invoice->refresh();
        $this->assertEquals('1000.00', $this->invoice->amount_paid);
        $this->assertEquals('0.00', $this->invoice->balance_due);
        $this->assertEquals('paid', $this->invoice->status);
    }

    #[Test]
    public function test_partial_payment_marks_invoice_as_partial(): void
    {
        $this->actingAs($this->accountsUser, 'admin')
            ->post(route('admin.payments.store'), [
                'invoice_id' => $this->invoice->id,
                'payment_date' => now()->toDateString(),
                'amount' => 250,
                'mode' => 'cheque',
                'reference_no' => 'CHQ-001',
            ]);

        $this->invoice->refresh();
        $this->assertEquals('partial', $this->invoice->status);
        $this->assertEquals('250.00', $this->invoice->amount_paid);
        $this->assertEquals('750.00', $this->invoice->balance_due);
    }
}
