<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ServiceTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUsers;

class ServiceTicketTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    protected Admin $serviceUser;

    protected Customer $customer;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();
        $this->serviceUser = $this->createAdminWithRole('Service', 'service@test.com');

        // Service role has customers.view but not customers.create,
        // so we create the customer directly
        $this->customer = Customer::create([
            'code' => 'CUST-0001',
            'name' => 'Test Customer',
            'status' => 'active',
            'created_by' => $this->serviceUser->id,
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
            'created_by' => $this->serviceUser->id,
        ]);
    }

    #[Test]
    public function test_service_user_can_view_tickets(): void
    {
        $response = $this->actingAs($this->serviceUser, 'admin')
            ->get(route('admin.service-tickets.index'));

        $response->assertStatus(200);
    }

    #[Test]
    public function test_service_user_can_create_ticket(): void
    {
        $response = $this->actingAs($this->serviceUser, 'admin')
            ->post(route('admin.service-tickets.store'), [
                'customer_id' => $this->customer->id,
                'product_id' => $this->product->id,
                'issue_description' => 'Product not working correctly after installation.',
                'priority' => 'high',
                'status' => 'open',
                'assigned_to' => $this->serviceUser->id,
            ]);

        $response->assertRedirect(route('admin.service-tickets.index'));

        $ticket = ServiceTicket::first();
        $this->assertNotNull($ticket);
        $this->assertMatchesRegularExpression('/^SRV-\d{4}-\d{4}$/', $ticket->ticket_number);
        $this->assertEquals('high', $ticket->priority);
        $this->assertEquals('open', $ticket->status);
        $this->assertNotNull($ticket->opened_at);
    }

    #[Test]
    public function test_service_user_can_add_comment(): void
    {
        $ticket = ServiceTicket::create([
            'ticket_number' => 'SRV-2026-0001',
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'issue_description' => 'Test issue',
            'priority' => 'medium',
            'status' => 'open',
            'opened_at' => now(),
            'created_by' => $this->serviceUser->id,
        ]);

        $response = $this->actingAs($this->serviceUser, 'admin')
            ->post(route('admin.service-tickets.add-comment', $ticket->id), [
                'comment' => 'Investigated the issue. Needs replacement part.',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('service_ticket_comments', [
            'service_ticket_id' => $ticket->id,
            'comment' => 'Investigated the issue. Needs replacement part.',
            'created_by' => $this->serviceUser->id,
        ]);
    }

    #[Test]
    public function test_ticket_closed_sets_closed_at(): void
    {
        $ticket = ServiceTicket::create([
            'ticket_number' => 'SRV-2026-0001',
            'customer_id' => $this->customer->id,
            'issue_description' => 'Test issue for closure',
            'priority' => 'low',
            'status' => 'open',
            'opened_at' => now(),
            'created_by' => $this->serviceUser->id,
        ]);

        $this->assertNull($ticket->closed_at);

        $response = $this->actingAs($this->serviceUser, 'admin')
            ->put(route('admin.service-tickets.update', $ticket->id), [
                'customer_id' => $this->customer->id,
                'issue_description' => 'Test issue for closure',
                'priority' => 'low',
                'status' => 'closed',
                'resolution_notes' => 'Issue resolved by replacing the component.',
            ]);

        $response->assertRedirect(route('admin.service-tickets.index'));

        $ticket->refresh();
        $this->assertEquals('closed', $ticket->status);
        $this->assertNotNull($ticket->closed_at);
    }
}
