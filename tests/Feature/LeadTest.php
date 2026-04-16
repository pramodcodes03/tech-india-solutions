<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\LeadActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUsers;

class LeadTest extends TestCase
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
    public function test_sales_user_can_view_leads(): void
    {
        $response = $this->actingAs($this->salesUser, 'admin')
            ->get(route('admin.leads.index'));

        $response->assertStatus(200);
    }

    #[Test]
    public function test_sales_user_can_create_lead(): void
    {
        $response = $this->actingAs($this->salesUser, 'admin')
            ->post(route('admin.leads.store'), [
                'name' => 'Test Lead',
                'company' => 'Lead Company',
                'phone' => '9876543210',
                'email' => 'lead@example.com',
                'source' => 'website',
                'status' => 'new',
                'expected_value' => 50000,
            ]);

        $response->assertRedirect(route('admin.leads.index'));
        $this->assertDatabaseHas('leads', [
            'name' => 'Test Lead',
            'company' => 'Lead Company',
            'source' => 'website',
            'status' => 'new',
        ]);
    }

    #[Test]
    public function test_lead_can_be_converted_to_customer(): void
    {
        $lead = Lead::create([
            'code' => 'LEAD-0001',
            'name' => 'Convertable Lead',
            'company' => 'Lead Corp',
            'email' => 'convert@example.com',
            'phone' => '9876543210',
            'source' => 'referral',
            'status' => 'qualified',
            'created_by' => $this->salesUser->id,
        ]);

        $response = $this->actingAs($this->salesUser, 'admin')
            ->post(route('admin.leads.convert', $lead->id));

        $response->assertRedirect();
        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'status' => 'won',
        ]);
        $this->assertDatabaseHas('customers', [
            'name' => 'Convertable Lead',
            'company' => 'Lead Corp',
        ]);
    }

    #[Test]
    public function test_lead_conversion_creates_customer_and_activity(): void
    {
        $lead = Lead::create([
            'code' => 'LEAD-0001',
            'name' => 'Activity Lead',
            'company' => 'Activity Corp',
            'email' => 'activity@example.com',
            'phone' => '9876543210',
            'source' => 'walk-in',
            'status' => 'new',
            'created_by' => $this->salesUser->id,
        ]);

        $this->actingAs($this->salesUser, 'admin')
            ->post(route('admin.leads.convert', $lead->id));

        $customer = Customer::where('name', 'Activity Lead')->first();
        $this->assertNotNull($customer);
        $this->assertMatchesRegularExpression('/^CUST-\d{4}$/', $customer->code);

        $activity = LeadActivity::where('lead_id', $lead->id)
            ->where('type', 'converted')
            ->first();
        $this->assertNotNull($activity);
        $this->assertEquals('converted', $activity->type);
    }

    #[Test]
    public function test_won_lead_cannot_be_converted_again(): void
    {
        $lead = Lead::create([
            'code' => 'LEAD-0001',
            'name' => 'Already Won',
            'company' => 'Won Corp',
            'source' => 'website',
            'status' => 'won',
            'created_by' => $this->salesUser->id,
        ]);

        $response = $this->actingAs($this->salesUser, 'admin')
            ->post(route('admin.leads.convert', $lead->id));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Should not create a new customer from this lead
        $this->assertEquals(0, Customer::count());
    }
}
