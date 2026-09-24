<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Invoice;
use App\Support\Tenancy\CurrentBusiness;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUsers;

/**
 * Business access for a non-super admin — the accountant who audits several
 * companies but must not be handed the whole system to do it.
 *
 * Switching used to be gated on the Super Admin role in three places at once:
 * the switcher was not rendered, the switch action 403'd, and the tenancy
 * middleware re-pinned the admin to their own business on every request. All
 * three now consult the same assignment.
 */
class BusinessAccessTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    private Business $home;

    private Business $client;

    private Business $stranger;

    private Admin $accountant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();

        $this->home = Business::create([
            'name' => 'Home Co', 'slug' => 'home-co',
            'currency_code' => 'INR', 'currency_symbol' => '₹',
        ]);
        $this->client = Business::create([
            'name' => 'Client Co', 'slug' => 'client-co',
            'currency_code' => 'INR', 'currency_symbol' => '₹',
        ]);
        $this->stranger = Business::create([
            'name' => 'Stranger Co', 'slug' => 'stranger-co',
            'currency_code' => 'INR', 'currency_symbol' => '₹',
        ]);

        app(CurrentBusiness::class)->set($this->home);

        $this->accountant = Admin::create([
            'name' => 'CA Ravi', 'email' => 'ca@leave.test',
            'password' => bcrypt('password'), 'phone' => '9990001111',
            'status' => 'active', 'business_id' => $this->home->id,
        ]);
        $this->accountant->assignRole('Accounts');
    }

    private function assignClient(): void
    {
        $this->accountant->businesses()->sync([$this->client->id => []]);
    }

    // ── The assignment itself ────────────────────────────────────────────

    #[Test]
    public function an_admin_can_reach_their_own_business_without_any_assignment(): void
    {
        $this->assertTrue($this->accountant->canAccessBusiness($this->home->id));
        $this->assertFalse($this->accountant->canAccessBusiness($this->client->id));
    }

    #[Test]
    public function an_assignment_grants_access_to_that_business_only(): void
    {
        $this->assignClient();

        $this->assertTrue($this->accountant->canAccessBusiness($this->client->id));
        $this->assertFalse($this->accountant->canAccessBusiness($this->stranger->id));
    }

    #[Test]
    public function the_accessible_list_includes_the_home_business(): void
    {
        $this->assignClient();

        $names = $this->accountant->accessibleBusinesses()->pluck('name')->all();

        $this->assertContains('Home Co', $names);
        $this->assertContains('Client Co', $names);
        $this->assertNotContains('Stranger Co', $names);
    }

    #[Test]
    public function the_switcher_stays_hidden_with_nothing_to_switch_to(): void
    {
        // One business is not a choice, so offering a switcher is just noise.
        $this->assertFalse($this->accountant->fresh()->canSwitchBusiness());

        $this->assignClient();
        $this->assertTrue($this->accountant->fresh()->canSwitchBusiness());
    }

    #[Test]
    public function an_inactive_business_drops_out_of_the_list(): void
    {
        $this->assignClient();
        $this->client->update(['is_active' => false]);

        $this->assertNotContains(
            'Client Co',
            $this->accountant->fresh()->accessibleBusinesses()->pluck('name')->all(),
        );
    }

    // ── Switching ────────────────────────────────────────────────────────

    #[Test]
    public function an_assigned_admin_can_switch_into_that_business(): void
    {
        $this->assignClient();

        $this->actingAs($this->accountant, 'admin')
            ->post(route('admin.businesses.switch', $this->client))
            ->assertRedirect();

        $this->assertSame($this->client->id, session('business_id'));
    }

    #[Test]
    public function an_admin_cannot_switch_into_a_business_they_were_not_assigned(): void
    {
        $this->assignClient();

        $this->actingAs($this->accountant, 'admin')
            ->post(route('admin.businesses.switch', $this->stranger))
            ->assertForbidden();
    }

    #[Test]
    public function switching_requires_the_switch_permission(): void
    {
        $this->assignClient();
        $this->accountant->roles()->first()->revokePermissionTo('businesses.switch');
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->actingAs($this->accountant->fresh(), 'admin')
            ->post(route('admin.businesses.switch', $this->client))
            ->assertForbidden();
    }

    // ── What the switch actually changes ─────────────────────────────────

    #[Test]
    public function after_switching_the_admin_sees_the_other_businesses_invoices(): void
    {
        $this->assignClient();

        $customer = Customer::withoutGlobalScopes()->create([
            'business_id' => $this->client->id, 'code' => 'CUST-1',
            'name' => 'Client Customer', 'email' => 'cust@client.test', 'status' => 'active',
        ]);
        Invoice::withoutGlobalScopes()->create([
            'business_id' => $this->client->id, 'customer_id' => $customer->id,
            'invoice_number' => 'INV-CLIENT-1', 'invoice_date' => '2026-09-01',
            'due_date' => '2026-09-30', 'grand_total' => 1000, 'status' => 'unpaid',
        ]);

        $this->actingAs($this->accountant, 'admin')
            ->withSession(['business_id' => $this->client->id])
            ->get(route('admin.invoices.index'))
            ->assertOk()
            ->assertSee('INV-CLIENT-1');
    }

    #[Test]
    public function a_session_naming_an_unassigned_business_is_ignored(): void
    {
        $this->assignClient();

        // A hand-edited session must not become a way in. The middleware falls
        // back to the home business rather than honouring the value.
        $this->actingAs($this->accountant, 'admin')
            ->withSession(['business_id' => $this->stranger->id])
            ->get(route('admin.invoices.index'))
            ->assertOk();

        $this->assertSame($this->home->id, app(CurrentBusiness::class)->id());
    }

    #[Test]
    public function an_admin_with_no_assignment_stays_pinned_to_their_own_business(): void
    {
        $this->actingAs($this->accountant, 'admin')
            ->withSession(['business_id' => $this->client->id])
            ->get(route('admin.invoices.index'))
            ->assertOk();

        $this->assertSame($this->home->id, app(CurrentBusiness::class)->id());
    }

    // ── Granting ─────────────────────────────────────────────────────────

    #[Test]
    public function a_super_admin_can_grant_and_clear_business_access(): void
    {
        $super = $this->createSuperAdmin();

        $this->actingAs($super, 'admin')->withSession(['business_id' => $this->home->id])
            ->put(route('admin.admin-users.update', $this->accountant->id), [
                'name' => 'CA Ravi', 'email' => 'ca@leave.test', 'phone' => '9990001111',
                'status' => 'active',
                'role_id' => $this->accountant->roles->first()->id,
                'business_ids' => [$this->client->id],
                'business_ids_submitted' => 1,
            ])->assertRedirect();

        $this->assertTrue($this->accountant->fresh()->canAccessBusiness($this->client->id));

        // Unticking everything posts no business_ids at all; the hidden marker
        // is what makes clearing possible rather than a silent no-op.
        $this->actingAs($super, 'admin')->withSession(['business_id' => $this->home->id])
            ->put(route('admin.admin-users.update', $this->accountant->id), [
                'name' => 'CA Ravi', 'email' => 'ca@leave.test', 'phone' => '9990001111',
                'status' => 'active',
                'role_id' => $this->accountant->roles->first()->id,
                'business_ids_submitted' => 1,
            ])->assertRedirect();

        $this->assertFalse($this->accountant->fresh()->canAccessBusiness($this->client->id));
    }

    #[Test]
    public function a_super_admin_still_reaches_every_business(): void
    {
        $super = $this->createSuperAdmin();

        $this->assertTrue($super->canAccessBusiness($this->stranger->id));
        $this->assertTrue($super->canSwitchBusiness());
    }
}
