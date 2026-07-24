<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AttendanceRegularization;
use App\Models\Business;
use App\Models\Candidate;
use App\Models\Employee;
use App\Models\ExpenseCategory;
use App\Models\InternalTicket;
use App\Models\LeaveType;
use App\Models\Product;
use App\Models\ReimbursementClaim;
use App\Models\Requisition;
use App\Models\SalaryStructure;
use App\Services\ArrearsService;
use App\Services\AttendanceService;
use App\Services\LeaveAccrualService;
use App\Services\RecruitmentService;
use App\Services\StatutoryService;
use App\Services\TdsService;
use App\Support\HrSettings;
use App\Support\Tenancy\CurrentBusiness;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUsers;

class HrmsAddonsTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    protected Business $business;
    protected Admin $admin;
    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();

        $this->business = Business::create([
            'name' => 'Test Co', 'slug' => 'test-co',
            'currency_code' => 'INR', 'currency_symbol' => '₹',
        ]);
        app(CurrentBusiness::class)->set($this->business);

        // A per-business Admin → tenancy middleware resolves business from it,
        // and the Admin role has every module permission.
        $this->admin = Admin::create([
            'name' => 'Admin', 'email' => 'admin@test.com',
            'password' => bcrypt('password'), 'phone' => '9990001111',
            'status' => 'active', 'business_id' => $this->business->id,
        ]);
        $this->admin->assignRole('Admin');

        $this->employee = Employee::create([
            'business_id' => $this->business->id, 'employee_code' => 'EMP-001',
            'email' => 'asha@test.com', 'first_name' => 'Asha', 'last_name' => 'Verma',
            'status' => 'active', 'password' => 'secret', 'joining_date' => '2025-01-01',
        ]);
    }

    private function adminGet(string $route, array $params = [])
    {
        return $this->actingAs($this->admin, 'admin')->get(route($route, $params));
    }

    // ───────────────────────── Module 1: Recruitment ─────────────────────────

    #[Test]
    public function recruitment_index_and_create_candidate_with_history(): void
    {
        $this->adminGet('admin.hr.recruitment.index')->assertStatus(200);

        $res = $this->actingAs($this->admin, 'admin')->post(route('admin.hr.recruitment.store'), [
            'first_name' => 'Ravi', 'source' => 'referral', 'applied_at' => '2026-06-01',
        ]);
        $res->assertRedirect();

        $candidate = Candidate::where('first_name', 'Ravi')->first();
        $this->assertNotNull($candidate);
        $this->assertDatabaseHas('candidate_stage_histories', [
            'candidate_id' => $candidate->id, 'action' => 'created',
        ]);
    }

    #[Test]
    public function recruitment_move_to_hired_stage_sets_status(): void
    {
        $service = app(RecruitmentService::class);
        $service->ensureStages($this->business->id);
        $candidate = $service->create(['business_id' => $this->business->id, 'first_name' => 'Hire Me', 'source' => 'walkin']);
        $hiredStage = \App\Models\RecruitmentStage::where('business_id', $this->business->id)->where('type', 'hired')->first();

        $service->moveToStage($candidate, $hiredStage, 'Selected');

        $this->assertEquals('hired', $candidate->fresh()->status);
    }

    // ─────────────────── Module 2: Payroll — TDS, EPS, Arrears ────────────────

    #[Test]
    public function tds_engine_computes_progressive_slab_tax(): void
    {
        $tax = app(TdsService::class)->annualTax(1_000_000, $this->business->id);
        // 0–3L:0, 3–7L:5%=20k, 7–9.5L taxable(after 50k std ded → 9.5L):
        // slabs applied progressively then 4% cess → positive, non-trivial.
        $this->assertGreaterThan(0, $tax);
        $this->assertEquals(0.0, app(TdsService::class)->annualTax(250_000, $this->business->id));
    }

    #[Test]
    public function arrears_service_computes_backdated_revision(): void
    {
        // Old structure (lower) ended; new current structure backdated 3 months.
        SalaryStructure::create([
            'business_id' => $this->business->id, 'employee_id' => $this->employee->id,
            'effective_from' => Carbon::now()->subMonths(8)->toDateString(),
            'effective_to' => Carbon::now()->subMonths(3)->toDateString(),
            'gross_monthly' => 30000, 'is_current' => false,
        ]);
        SalaryStructure::create([
            'business_id' => $this->business->id, 'employee_id' => $this->employee->id,
            'effective_from' => Carbon::now()->subMonths(3)->startOfMonth()->toDateString(),
            'gross_monthly' => 40000, 'is_current' => true,
        ]);

        $result = app(ArrearsService::class)->compute($this->employee);

        $this->assertTrue($result['applicable']);
        $this->assertEquals(10000, $result['monthly_diff']);
        $this->assertGreaterThanOrEqual(2, $result['months']);
        $this->assertEquals($result['monthly_diff'] * $result['months'], $result['arrears']);
    }

    #[Test]
    public function statutory_register_splits_eps_from_pf(): void
    {
        \App\Models\Payslip::create([
            'business_id' => $this->business->id, 'employee_id' => $this->employee->id,
            'payslip_code' => 'PS-1', 'month' => 6, 'year' => 2026,
            'period_start' => '2026-06-01', 'period_end' => '2026-06-30',
            'working_days' => 30, 'paid_days' => 30, 'lop_days' => 0,
            'basic' => 20000, 'gross_earnings' => 40000, 'pf' => 2400, 'esi' => 0,
            'professional_tax' => 200, 'tds' => 0, 'total_deductions' => 2600, 'net_pay' => 37400, 'status' => 'generated',
        ]);

        $rows = app(StatutoryService::class)->register(6, 2026);
        $row = $rows->first();
        // EPS = 8.33% of min(basic, cap=15000) = 8.33% of 15000 = 1249.5
        $this->assertEqualsWithDelta(1249.5, $row['eps'], 1);
        $this->assertGreaterThanOrEqual(0, $row['pf_employer_epf']);
    }

    // ───────────────── Module 3: Attendance regularization ───────────────────

    #[Test]
    public function employee_raises_regularization_and_admin_approves(): void
    {
        $res = $this->actingAs($this->employee, 'employee')->post(route('employee.regularizations.store'), [
            'date' => Carbon::today()->toDateString(),
            'request_type' => 'missed_punch',
            'expected_in' => '09:30', 'expected_out' => '18:30',
            'reason' => 'Biometric did not capture my punch.',
        ]);
        $res->assertRedirect();

        $reg = AttendanceRegularization::first();
        $this->assertNotNull($reg);
        $this->assertNotNull($reg->sla_due_at, 'TAT due time should be stamped');

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.hr.regularizations.approve', $reg))
            ->assertRedirect();

        $this->assertEquals('approved', $reg->fresh()->status);
        $this->assertDatabaseHas('attendance', [
            'employee_id' => $this->employee->id, 'source' => 'regularization',
        ]);
    }

    // ───────────────── Module 4: Leave accrual + year-end ─────────────────────

    #[Test]
    public function leave_accrual_credits_eligible_employee(): void
    {
        $type = LeaveType::create([
            'business_id' => $this->business->id, 'code' => 'EL', 'name' => 'Earned Leave',
            'annual_quota' => 6, 'accrual_enabled' => true, 'accrual_rate' => 0.5,
            'accrual_frequency' => 'monthly', 'accrue_after_probation' => false, 'status' => 'active',
        ]);

        $result = app(LeaveAccrualService::class)->accrueAll(Carbon::create(2026, 6, 1));

        $this->assertEquals(1, $result['credited']);
        $this->assertDatabaseHas('leave_balances', [
            'employee_id' => $this->employee->id, 'leave_type_id' => $type->id, 'allocated' => 0.5,
        ]);

        // Idempotent: a second run the same month credits nothing more.
        $again = app(LeaveAccrualService::class)->accrueAll(Carbon::create(2026, 6, 15));
        $this->assertEquals(0, $again['credited']);
    }

    #[Test]
    public function backdated_leave_beyond_window_is_rejected(): void
    {
        HrSettings::set('leave_application_window_hours', 72, 'leave');
        LeaveType::create(['business_id' => $this->business->id, 'code' => 'CL', 'name' => 'Casual', 'annual_quota' => 6, 'status' => 'active']);
        $type = LeaveType::first();

        $res = $this->actingAs($this->employee, 'employee')->post(route('employee.leaves.store'), [
            'leave_type_id' => $type->id,
            'from_date' => Carbon::today()->subDays(10)->toDateString(),
            'to_date' => Carbon::today()->subDays(10)->toDateString(),
            'day_portion' => 'full', 'reason' => 'Old leave attempt',
        ]);

        $res->assertSessionHas('error');
        $this->assertEquals(0, \App\Models\LeaveRequest::count());
    }

    // ───────────────── Module 5: Reimbursement + Requisition ──────────────────

    #[Test]
    public function employee_submits_reimbursement_and_admin_approves(): void
    {
        Storage::fake('public');
        $cat = ExpenseCategory::create(['business_id' => $this->business->id, 'name' => 'Travel', 'slug' => 'travel', 'is_active' => true]);

        $this->actingAs($this->employee, 'employee')->post(route('employee.reimbursements.store'), [
            'title' => 'Taxi', 'amount' => 500, 'claim_date' => Carbon::today()->toDateString(),
            'expense_category_id' => $cat->id, 'bill' => UploadedFile::fake()->create('bill.pdf', 50, 'application/pdf'),
        ])->assertRedirect();

        $claim = ReimbursementClaim::first();
        $this->assertEquals('submitted', $claim->status);

        $this->actingAs($this->admin, 'admin')->post(route('admin.reimbursements.review', $claim), [
            'status' => 'approved', 'approved_amount' => 500,
        ])->assertRedirect();

        $this->assertEquals('approved', $claim->fresh()->status);
    }

    #[Test]
    public function requisition_approval_chain_completes(): void
    {
        $res = $this->actingAs($this->admin, 'admin')->post(route('admin.requisitions.store'), [
            'category' => 'it_equipment', 'title' => 'Laptops', 'requested_amount' => 200000,
        ]);
        $res->assertRedirect();

        $req = Requisition::first();
        $this->assertEquals('pending', $req->status);
        $this->assertGreaterThanOrEqual(1, $req->approvals()->count());

        $this->actingAs($this->admin, 'admin')->post(route('admin.requisitions.approve', $req))->assertRedirect();
        $this->assertEquals('approved', $req->fresh()->status);
    }

    // ───────────────── Module 6: Document verification ────────────────────────

    #[Test]
    public function employee_uploads_document_and_admin_verifies(): void
    {
        Storage::fake('public');
        $this->actingAs($this->employee, 'employee')->post(route('employee.documents.store'), [
            'doc_type' => 'pan', 'title' => 'PAN Card',
            'file' => UploadedFile::fake()->create('pan.pdf', 30, 'application/pdf'),
        ])->assertRedirect();

        $doc = \App\Models\EmployeeDocument::first();
        $this->assertEquals('pending', $doc->verification_status);
        $this->assertDatabaseHas('employee_document_verifications', ['employee_document_id' => $doc->id, 'action' => 'uploaded']);

        $this->actingAs($this->admin, 'admin')->post(route('admin.hr.employee-documents.verify', $doc), ['remarks' => 'ok'])->assertRedirect();
        $this->assertEquals('verified', $doc->fresh()->verification_status);
    }

    // ───────────────── Module 7: Internal helpdesk ────────────────────────────

    #[Test]
    public function employee_raises_internal_ticket_and_admin_resolves(): void
    {
        $this->actingAs($this->employee, 'employee')->post(route('employee.tickets.store'), [
            'department' => 'it', 'subject' => 'Laptop slow', 'description' => 'Very slow boot', 'priority' => 'high',
        ])->assertRedirect();

        $ticket = InternalTicket::first();
        $this->assertEquals('open', $ticket->status);
        $this->assertNotNull($ticket->tat_due_at);

        $this->actingAs($this->admin, 'admin')->post(route('admin.hr.internal-tickets.status', $ticket), ['status' => 'resolved'])->assertRedirect();
        $this->assertEquals('resolved', $ticket->fresh()->status);
    }

    // ───────────────── Module 8: Asset bulk operations ────────────────────────

    #[Test]
    public function bulk_change_asset_status_updates_all_selected(): void
    {
        \App\Models\AssetStatus::create(['business_id' => $this->business->id, 'key' => 'retired', 'label' => 'Retired', 'is_active' => true, 'sort_order' => 1]);
        $cat = AssetCategory::create(['business_id' => $this->business->id, 'code' => 'CAT-1', 'name' => 'Laptops']);
        $a1 = Asset::create(['business_id' => $this->business->id, 'asset_code' => 'A1', 'name' => 'L1', 'category_id' => $cat->id, 'status' => 'in_storage', 'condition_rating' => 'good']);
        $a2 = Asset::create(['business_id' => $this->business->id, 'asset_code' => 'A2', 'name' => 'L2', 'category_id' => $cat->id, 'status' => 'in_storage', 'condition_rating' => 'good']);

        $this->actingAs($this->admin, 'admin')->post(route('admin.assets.bulk.apply'), [
            'asset_ids' => [$a1->id, $a2->id], 'action' => 'change_status', 'status' => 'retired',
        ])->assertRedirect();

        $this->assertEquals('retired', $a1->fresh()->status);
        $this->assertEquals('retired', $a2->fresh()->status);
    }

    // ───────────────── Module 9: Lead product + stage log ─────────────────────

    #[Test]
    public function lead_status_change_records_stage_log(): void
    {
        $pcat = \App\Models\ProductCategory::create(['business_id' => $this->business->id, 'name' => 'General', 'slug' => 'general']);
        $product = Product::create(['business_id' => $this->business->id, 'category_id' => $pcat->id, 'code' => 'PRD-1', 'name' => 'Widget', 'selling_price' => 100, 'purchase_price' => 50]);
        $this->actingAs($this->admin, 'admin')->post(route('admin.leads.store'), [
            'name' => 'Lead A', 'source' => 'website', 'status' => 'new',
            'product_id' => $product->id, 'lead_date' => Carbon::today()->toDateString(),
        ])->assertRedirect();

        $lead = \App\Models\Lead::first();
        $this->assertEquals($product->id, $lead->product_id);

        $this->actingAs($this->admin, 'admin')->patch(route('admin.leads.update-status', $lead->id), ['status' => 'qualified', 'remarks' => 'Good fit'])->assertRedirect();
        $this->assertDatabaseHas('lead_stage_logs', ['lead_id' => $lead->id, 'to_status' => 'qualified', 'remarks' => 'Good fit']);
    }

    // ───────────────── Module 10: Bulk import (validate→confirm) ──────────────

    #[Test]
    public function employee_bulk_import_validates_and_imports(): void
    {
        // The preview HTTP step works and surfaces row errors.
        $csv = "First Name,Last Name,Email,Phone,Department,Designation,Joining Date,Employment Type\n".
               "Neha,Singh,neha@test.com,9876500000,,,2026-06-01,permanent\n".
               ",,bad,,,,,\n"; // invalid row (no first name)
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.imports.preview', 'employees'), ['file' => UploadedFile::fake()->createWithContent('employees.csv', $csv)])
            ->assertStatus(200);

        // The validate→import pipeline itself (cross-request session isn't
        // available under the array session driver, so exercise it directly).
        $svc = app(\App\Services\Import\BulkImportService::class);
        $importer = $svc->importer('employees');
        $rows = [
            ['first name' => 'Neha', 'last name' => 'Singh', 'email' => 'neha@test.com', 'phone' => '9876500000', 'department' => '', 'designation' => '', 'joining date' => '2026-06-01', 'employment type' => 'permanent'],
            ['first name' => '', 'last name' => '', 'email' => 'bad', 'phone' => '', 'department' => '', 'designation' => '', 'joining date' => '', 'employment type' => ''],
        ];
        $validation = $svc->validate($rows, $importer, $this->business->id);
        $this->assertCount(1, $validation['valid']);
        $this->assertCount(1, $validation['errors']);

        $result = $svc->import($validation['valid'], $importer, $this->business->id, 'employees.csv');
        $this->assertEquals(1, $result['imported'], 'Import errors: '.json_encode($result['errors']));
        $this->assertDatabaseHas('employees', ['email' => 'neha@test.com']);
        $this->assertDatabaseHas('bulk_import_logs', ['type' => 'employees', 'imported' => 1]);
    }

    // ───────────────── Module 11: Report builder + HR reports ─────────────────

    #[Test]
    public function report_builder_builds_employee_report(): void
    {
        $this->actingAs($this->admin, 'admin')->post(route('admin.report-builder.build'), [
            'module' => 'employees', 'columns' => ['employee_code', 'full_name', 'esi_number'],
        ])->assertStatus(200)->assertViewHas('result');

        $this->adminGet('admin.hr.reports.employee-master')->assertStatus(200);
        $this->adminGet('admin.hr.reports.payroll')->assertStatus(200);
    }

    // ───────────────── Module 2: Break-policy → half-day LOP ──────────────────

    #[Test]
    public function excess_break_marks_attendance_half_day(): void
    {
        HrSettings::set('break_half_day_minutes', 60, 'statutory');

        app(AttendanceService::class)->upsert([
            'business_id' => $this->business->id, 'employee_id' => $this->employee->id,
            'date' => '2026-06-10', 'check_in' => '09:30:00', 'check_out' => '18:30:00',
            'status' => 'present', 'break_minutes' => 90, 'source' => 'manual',
        ]);

        $this->assertDatabaseHas('attendance', [
            'employee_id' => $this->employee->id, 'date' => '2026-06-10', 'status' => 'half_day',
        ]);
    }

    // ───────────── Module 2b: worked-hours → status boundaries ────────────────

    /**
     * Strict boundaries on the hours→status rule:
     *   < 4h30m        → absent   (e.g. 4h29m is NOT a half day)
     *   4h30m to < 8h  → half_day (4h30m exactly is a half day)
     *   >= 8h          → present  (8h exactly is present)
     *
     * deriveStatus() is exercised directly (it's the single source of truth for
     * the hours→status mapping) so the assertion isn't muddied by week-off /
     * holiday / approved-leave promotion that the upsert() path layers on top.
     */
    #[Test]
    public function worked_hours_map_to_strict_attendance_status_boundaries(): void
    {
        $svc = app(AttendanceService::class);
        $ref = new \ReflectionMethod($svc, 'deriveStatus');
        $ref->setAccessible(true);

        $derive = fn (string $in, string $out): string => $ref->invoke($svc, [
            'check_in' => $in, 'check_out' => $out,
        ]);

        // Absent: anything strictly under 4h30m.
        $this->assertEquals('absent', $derive('09:00:00', '09:05:00'), 'a few minutes → absent');
        $this->assertEquals('absent', $derive('09:00:00', '13:29:00'), '4h29m → absent');

        // Half day: 4h30m up to (not including) 8h.
        $this->assertEquals('half_day', $derive('09:00:00', '13:30:00'), '4h30m exactly → half_day');
        $this->assertEquals('half_day', $derive('09:00:00', '16:59:00'), '7h59m → half_day');

        // Present: 8h and above.
        $this->assertEquals('present', $derive('09:00:00', '17:00:00'), '8h exactly → present');
        $this->assertEquals('present', $derive('09:00:00', '18:30:00'), '9h30m → present');

        // Mid-day (no check-out) stays present until punch-out recomputes it.
        $this->assertEquals('present', $derive('09:00:00', ''), 'punched in, not out → present');
    }
}
