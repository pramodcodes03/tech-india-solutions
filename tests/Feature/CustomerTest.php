<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUsers;

class CustomerTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    protected Admin $salesUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();
        $this->salesUser = $this->createAdminWithRole('Sales', 'sales@test.com');
    }

    #[Test]
    public function test_sales_user_can_view_customers(): void
    {
        $response = $this->actingAs($this->salesUser, 'admin')
            ->get(route('admin.customers.index'));

        $response->assertStatus(200);
    }

    #[Test]
    public function test_sales_user_can_create_customer(): void
    {
        $response = $this->actingAs($this->salesUser, 'admin')
            ->post(route('admin.customers.store'), [
                'name' => 'Test Customer',
                'company' => 'Test Company',
                'email' => 'customer@example.com',
                'phone' => '9876543210',
                'city' => 'Chennai',
                'state' => 'Tamil Nadu',
                'status' => 'active',
            ]);

        $response->assertRedirect(route('admin.customers.index'));
        $this->assertDatabaseHas('customers', [
            'name' => 'Test Customer',
            'company' => 'Test Company',
        ]);
    }

    #[Test]
    public function test_customer_code_is_auto_generated(): void
    {
        $this->actingAs($this->salesUser, 'admin')
            ->post(route('admin.customers.store'), [
                'name' => 'Auto Code Customer',
                'status' => 'active',
            ]);

        $customer = Customer::where('name', 'Auto Code Customer')->first();

        $this->assertNotNull($customer);
        $this->assertMatchesRegularExpression('/^CUST-\d{4}$/', $customer->code);
    }

    #[Test]
    public function test_sales_user_can_update_customer(): void
    {
        $customer = Customer::create([
            'code' => 'CUST-0001',
            'name' => 'Old Name',
            'status' => 'active',
            'created_by' => $this->salesUser->id,
        ]);

        $response = $this->actingAs($this->salesUser, 'admin')
            ->put(route('admin.customers.update', $customer->id), [
                'name' => 'Updated Name',
                'status' => 'active',
            ]);

        $response->assertRedirect(route('admin.customers.index'));
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Updated Name',
        ]);
    }

    #[Test]
    public function test_sales_user_can_delete_customer(): void
    {
        $customer = Customer::create([
            'code' => 'CUST-0001',
            'name' => 'Delete Me',
            'status' => 'active',
            'created_by' => $this->salesUser->id,
        ]);

        $response = $this->actingAs($this->salesUser, 'admin')
            ->delete(route('admin.customers.destroy', $customer->id));

        $response->assertRedirect(route('admin.customers.index'));
        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
    }

    #[Test]
    public function test_viewer_cannot_create_customer(): void
    {
        $viewer = $this->createAdminWithRole('Viewer', 'viewer@test.com');

        $response = $this->actingAs($viewer, 'admin')
            ->post(route('admin.customers.store'), [
                'name' => 'Should Not Exist',
                'status' => 'active',
            ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('customers', [
            'name' => 'Should Not Exist',
        ]);
    }
}
