<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Business;
use App\Models\Employee;
use App\Models\InternalTicket;
use App\Services\Documents\DocumentDataResolver;
use App\Support\DocumentCatalog;
use App\Support\Tenancy\CurrentBusiness;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUsers;

/**
 * The Helpdesk Report: its filters, its columns, its own permission and the
 * department restriction that can be put on a user.
 */
class HelpdeskReportTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    private Business $business;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();

        Carbon::setTestNow(Carbon::parse('2026-09-15 10:00:00'));

        $this->business = Business::create([
            'name' => 'Helpdesk Co', 'slug' => 'helpdesk-co',
            'currency_code' => 'INR', 'currency_symbol' => '₹',
        ]);
        app(CurrentBusiness::class)->set($this->business);

        $this->admin = Admin::create([
            'name' => 'Priya Admin', 'email' => 'priya@hd.test',
            'password' => bcrypt('password'), 'phone' => '9990001111',
            'status' => 'active', 'business_id' => $this->business->id,
        ]);
        $this->admin->assignRole('Admin');

        $employee = Employee::create([
            'business_id' => $this->business->id, 'employee_code' => 'EMP-001',
            'email' => 'asha@hd.test', 'first_name' => 'Asha', 'last_name' => 'Verma',
            'status' => 'active', 'password' => 'secret', 'joining_date' => '2025-01-01',
        ]);

        $this->ticket('INT-HR-1', 'hr', $employee->id, '2026-09-10 09:00:00', '2026-09-11 17:30:00');
        $this->ticket('INT-IT-1', 'it', $employee->id, '2026-09-12 11:00:00', null);
        $this->ticket('INT-IT-2', 'it', $employee->id, '2026-08-04 08:00:00', '2026-08-05 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function ticket(string $number, string $dept, int $employeeId, string $created, ?string $closed): void
    {
        $ticket = InternalTicket::create([
            'business_id' => $this->business->id, 'ticket_number' => $number,
            'employee_id' => $employeeId, 'department' => $dept,
            'subject' => $number.' subject', 'priority' => 'medium',
            'status' => $closed ? 'closed' : 'open',
            'closed_at' => $closed,
        ]);

        // created_at is set by the model; force it so the date filters have
        // something deterministic to bite on.
        DB::table('internal_tickets')->where('id', $ticket->id)->update(['created_at' => $created]);
    }

    private function render(array $params = [])
    {
        return $this->actingAs($this->admin, 'admin')
            ->get(route('admin.documents.render', array_merge(['key' => 'helpdesk_report'], $params)));
    }

    // ── Columns ──────────────────────────────────────────────────────────

    #[Test]
    public function the_report_names_the_department_and_both_timestamps(): void
    {
        $payload = app(DocumentDataResolver::class)
            ->resolve('helpdesk_report', null, []);

        $this->assertContains('Department', $payload['headings']);
        $this->assertContains('Generated Date & Time', $payload['headings']);
        $this->assertContains('Closed Date & Time', $payload['headings']);

        $this->assertContains('Emp Code', $payload['headings']);

        $hr = collect($payload['rows'])->firstWhere(0, 'INT-HR-1');
        $this->assertSame('HR', $hr[2]);
        // Employee code and name are separate columns, in that order.
        $this->assertSame('EMP-001', $hr[4]);
        $this->assertSame('Asha Verma', $hr[5]);
        $this->assertSame('10-09-2026 09:00 AM', $hr[9]);
        $this->assertSame('11-09-2026 05:30 PM', $hr[10]);
    }

    #[Test]
    public function an_open_ticket_shows_no_closed_time(): void
    {
        $payload = app(DocumentDataResolver::class)
            ->resolve('helpdesk_report', null, []);

        $open = collect($payload['rows'])->firstWhere(0, 'INT-IT-1');
        $this->assertSame('—', $open[10]);
    }

    // ── Filters ──────────────────────────────────────────────────────────

    private function rowsFor(array $filters): array
    {
        return app(DocumentDataResolver::class)
            ->resolve('helpdesk_report', null, $filters)['rows'];
    }

    #[Test]
    public function the_department_filter_narrows_the_report(): void
    {
        $this->actingAs($this->admin, 'admin');

        $this->assertCount(1, $this->rowsFor(['ticket_department' => 'hr']));
        $this->assertCount(2, $this->rowsFor(['ticket_department' => 'it']));
    }

    #[Test]
    public function the_month_year_and_date_filters_narrow_the_report(): void
    {
        $this->actingAs($this->admin, 'admin');

        $this->assertCount(2, $this->rowsFor(['month' => 9, 'year' => 2026]));
        $this->assertCount(1, $this->rowsFor(['month' => 8, 'year' => 2026]));
        $this->assertCount(1, $this->rowsFor(['date' => '2026-09-12']));
        $this->assertCount(3, $this->rowsFor(['month' => '', 'year' => '', 'date' => '']));
    }

    // ── Department restriction ───────────────────────────────────────────

    #[Test]
    public function an_unrestricted_admin_sees_every_department(): void
    {
        $this->actingAs($this->admin, 'admin');

        $this->assertSame([], $this->admin->helpdeskDepartments());
        $this->assertCount(3, $this->rowsFor([]));
    }

    #[Test]
    public function a_restricted_admin_only_sees_their_own_department(): void
    {
        DB::table('admin_ticket_departments')->insert([
            'admin_id' => $this->admin->id, 'department' => 'hr',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($this->admin->fresh(), 'admin');

        $rows = $this->rowsFor([]);
        $this->assertCount(1, $rows);
        $this->assertSame('INT-HR-1', $rows[0][0]);
    }

    #[Test]
    public function a_restricted_admin_cannot_widen_their_scope_with_a_filter(): void
    {
        DB::table('admin_ticket_departments')->insert([
            'admin_id' => $this->admin->id, 'department' => 'hr',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($this->admin->fresh(), 'admin');

        // Asking for IT must not hand over IT tickets.
        $this->assertCount(0, $this->rowsFor(['ticket_department' => 'it']));
    }

    // ── Permission ───────────────────────────────────────────────────────

    #[Test]
    public function the_helpdesk_report_has_its_own_permission_module(): void
    {
        $this->assertSame('helpdesk_reports', DocumentCatalog::permissionFor('helpdesk_report'));
        // Other reports still ride on the pack.
        $this->assertSame('documents_reports', DocumentCatalog::permissionFor('report_payroll'));
    }

    #[Test]
    public function an_admin_without_the_helpdesk_permission_is_refused(): void
    {
        $role = Role::firstOrCreate(['name' => 'Reports Only', 'guard_name' => 'admin']);
        $role->syncPermissions(['documents.view', 'documents_reports.view', 'documents_reports.generate']);

        $viewer = Admin::create([
            'name' => 'Reports Only', 'email' => 'ro@hd.test',
            'password' => bcrypt('password'), 'phone' => '9990002222',
            'status' => 'active', 'business_id' => $this->business->id,
        ]);
        $viewer->assignRole($role);

        $this->actingAs($viewer, 'admin')
            ->get(route('admin.documents.render', ['key' => 'helpdesk_report']))
            ->assertForbidden();
    }

    #[Test]
    public function a_helpdesk_only_admin_can_reach_and_generate_the_report(): void
    {
        // The role this permission module exists for: helpdesk rights and
        // nothing else — no documents.view, no documents_reports.*. The hub
        // used to 403 them and the sidebar hid the way in entirely, so the
        // report they were granted was unreachable.
        $lead = $this->helpdeskOnlyAdmin();

        $this->actingAs($lead, 'admin')
            ->get(route('admin.documents.index'))
            ->assertOk();

        $this->actingAs($lead, 'admin')
            ->get(route('admin.documents.render', ['key' => 'helpdesk_report']))
            ->assertOk();
    }

    #[Test]
    public function the_hub_offers_a_helpdesk_only_admin_that_one_document(): void
    {
        $lead = $this->helpdeskOnlyAdmin();

        $response = $this->actingAs($lead, 'admin')->get(route('admin.documents.index'));

        $response->assertOk();
        // The pack it lives in is shown...
        $response->assertSee('Helpdesk Report');
        // ...but not the rest of that pack, which they hold no rights to.
        $response->assertDontSee('CRM Pipeline Report');
        $response->assertDontSee('Budget vs Actual Statement');
    }

    #[Test]
    public function a_helpdesk_only_admin_still_cannot_open_other_documents(): void
    {
        $lead = $this->helpdeskOnlyAdmin();

        // Same pack, no self-governing permission of its own.
        $this->actingAs($lead, 'admin')
            ->get(route('admin.documents.render', ['key' => 'crm_pipeline']))
            ->assertForbidden();

        // And nothing outside it.
        $this->actingAs($lead, 'admin')
            ->get(route('admin.documents.render', ['key' => 'form16']))
            ->assertForbidden();
    }

    #[Test]
    public function an_admin_with_no_document_rights_at_all_cannot_open_the_hub(): void
    {
        $role = Role::firstOrCreate(['name' => 'Helpdesk Agent', 'guard_name' => 'admin']);
        $role->syncPermissions(['helpdesk.view']);

        $agent = Admin::create([
            'name' => 'Agent', 'email' => 'agent@hd.test',
            'password' => bcrypt('password'), 'phone' => '9990004444',
            'status' => 'active', 'business_id' => $this->business->id,
        ]);
        $agent->assignRole($role);

        $this->actingAs($agent, 'admin')
            ->get(route('admin.documents.index'))
            ->assertForbidden();
    }

    /** An admin holding helpdesk_reports.* and nothing else. */
    private function helpdeskOnlyAdmin(): Admin
    {
        $role = Role::firstOrCreate(['name' => 'IT Helpdesk Lead', 'guard_name' => 'admin']);
        $role->syncPermissions([
            'helpdesk.view', 'helpdesk.manage',
            'helpdesk_reports.view', 'helpdesk_reports.generate', 'helpdesk_reports.export',
        ]);

        $lead = Admin::create([
            'name' => 'Mandeep Lead', 'email' => 'lead@hd.test',
            'password' => bcrypt('password'), 'phone' => '9990003333',
            'status' => 'active', 'business_id' => $this->business->id,
        ]);
        $lead->assignRole($role);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $lead;
    }

    // ── Output formats ───────────────────────────────────────────────────

    #[Test]
    public function the_report_still_downloads_as_a_pdf(): void
    {
        $response = $this->render();

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
    }

    #[Test]
    public function the_report_downloads_as_a_spreadsheet(): void
    {
        $response = $this->render(['format' => 'xlsx']);

        $response->assertOk();
        $this->assertStringContainsString('.xlsx', $response->headers->get('content-disposition'));
    }

    #[Test]
    public function a_document_that_is_not_table_shaped_refuses_a_spreadsheet(): void
    {
        // Only documents that declare `excel` can produce one; a letter cannot.
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.documents.render', ['key' => 'crm_pipeline', 'format' => 'xlsx']))
            ->assertNotFound();
    }

    // ── Export permission (department-wise) ──────────────────────────────

    /** An admin who may read the report but not take it out of the system. */
    private function readOnlyAdmin(): Admin
    {
        $role = Role::firstOrCreate(['name' => 'Helpdesk Reader', 'guard_name' => 'admin']);
        $role->syncPermissions([
            'documents.view', 'helpdesk_reports.view', 'helpdesk_reports.generate',
        ]);

        $admin = Admin::create([
            'name' => 'Reader', 'email' => 'reader@hd.test',
            'password' => bcrypt('password'), 'phone' => '9990003333',
            'status' => 'active', 'business_id' => $this->business->id,
        ]);
        $admin->assignRole($role);

        return $admin;
    }

    #[Test]
    public function downloading_requires_the_export_permission(): void
    {
        $reader = $this->readOnlyAdmin();

        $this->actingAs($reader, 'admin')
            ->get(route('admin.documents.render', ['key' => 'helpdesk_report']))
            ->assertForbidden();

        $this->actingAs($reader, 'admin')
            ->get(route('admin.documents.render', ['key' => 'helpdesk_report', 'format' => 'xlsx']))
            ->assertForbidden();
    }

    #[Test]
    public function granting_export_opens_both_formats(): void
    {
        $reader = $this->readOnlyAdmin();
        $reader->roles->first()->givePermissionTo('helpdesk_reports.export');
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->actingAs($reader->fresh(), 'admin')
            ->get(route('admin.documents.render', ['key' => 'helpdesk_report']))
            ->assertOk();

        $this->actingAs($reader->fresh(), 'admin')
            ->get(route('admin.documents.render', ['key' => 'helpdesk_report', 'format' => 'xlsx']))
            ->assertOk();
    }

    #[Test]
    public function an_export_still_only_contains_the_departments_the_user_may_see(): void
    {
        // Export permission widens the format, never the scope.
        $reader = $this->readOnlyAdmin();
        $reader->roles->first()->givePermissionTo('helpdesk_reports.export');
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        DB::table('admin_ticket_departments')->insert([
            'admin_id' => $reader->id, 'department' => 'hr',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($reader->fresh(), 'admin');

        $rows = $this->rowsFor(['ticket_department' => 'it']);
        $this->assertCount(0, $rows);

        $this->assertCount(1, $this->rowsFor([]));
    }

    #[Test]
    public function other_documents_are_unaffected_by_the_export_gate(): void
    {
        // Only documents that declare an export_permission are gated by it.
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.documents.render', ['key' => 'report_payroll', 'month' => 9, 'year' => 2026]))
            ->assertOk();
    }
}
