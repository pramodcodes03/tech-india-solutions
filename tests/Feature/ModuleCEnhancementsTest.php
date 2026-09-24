<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Business;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PayrollAdjustment;
use App\Models\Payslip;
use App\Models\Penalty;
use App\Models\PenaltyType;
use App\Services\LeaveService;
use App\Services\PayrollService;
use App\Support\SuperAdminMask;
use App\Support\Tenancy\CurrentBusiness;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUsers;

/**
 * Module C — Super Admin masking, bulk payslip delete, Combined Leave.
 */
class ModuleCEnhancementsTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    protected Business $business;

    protected Admin $admin;

    protected Admin $superAdmin;

    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();

        Carbon::setTestNow(Carbon::parse('2026-09-07 10:00:00'));   // a Monday

        $this->business = Business::create([
            'name' => 'Module C Co', 'slug' => 'module-c-co',
            'currency_code' => 'INR', 'currency_symbol' => '₹',
        ]);
        app(CurrentBusiness::class)->set($this->business);

        $this->admin = Admin::create([
            'name' => 'Priya Admin', 'email' => 'priya@modc.test',
            'password' => bcrypt('password'), 'phone' => '9990001111',
            'status' => 'active', 'business_id' => $this->business->id,
        ]);
        $this->admin->assignRole('Admin');

        $this->superAdmin = Admin::create([
            'name' => 'Omkar Root', 'email' => 'root@modc.test',
            'password' => bcrypt('password'), 'phone' => '9990002222',
            'status' => 'active',
        ]);
        $this->superAdmin->assignRole('Super Admin');

        $department = Department::create([
            'business_id' => $this->business->id, 'name' => 'Operations', 'code' => 'OPS',
        ]);

        $this->employee = Employee::create([
            'business_id' => $this->business->id, 'employee_code' => 'EMP-001',
            'email' => 'asha@modc.test', 'first_name' => 'Asha', 'last_name' => 'Verma',
            'department_id' => $department->id,
            'status' => 'active', 'password' => 'secret', 'joining_date' => '2025-01-01',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ═══ 1. Super Admin masking ══════════════════════════════════════════

    #[Test]
    public function a_regular_admin_sees_the_super_admin_as_system_admin(): void
    {
        $this->actingAs($this->admin, 'admin');

        $this->assertTrue(SuperAdminMask::active());
        $this->assertSame('System Admin', SuperAdminMask::label($this->superAdmin));
        $this->assertSame('System Admin', $this->superAdmin->display_name);
    }

    #[Test]
    public function a_super_admin_sees_real_names(): void
    {
        $this->actingAs($this->superAdmin, 'admin');

        $this->assertFalse(SuperAdminMask::active());
        $this->assertSame('Omkar Root', SuperAdminMask::label($this->superAdmin));
        $this->assertSame('Omkar Root', $this->superAdmin->display_name);
    }

    #[Test]
    public function an_ordinary_admin_is_never_masked(): void
    {
        $this->actingAs($this->admin, 'admin');

        $this->assertSame('Priya Admin', $this->admin->display_name);
        $this->assertFalse(SuperAdminMask::masks($this->admin));
    }

    #[Test]
    public function employees_and_logged_out_visitors_see_the_mask(): void
    {
        // No admin session at all — masking must fail safe, not fail open.
        $this->assertTrue(SuperAdminMask::active());
        $this->assertSame('System Admin', SuperAdminMask::label($this->superAdmin));
    }

    #[Test]
    public function the_underlying_record_is_untouched(): void
    {
        $this->actingAs($this->admin, 'admin');

        // The display is masked, the stored column is not — this is what keeps
        // the audit trail usable for compliance.
        $this->assertSame('System Admin', $this->superAdmin->display_name);
        $this->assertSame('Omkar Root', $this->superAdmin->name);
        $this->assertDatabaseHas('admins', ['id' => $this->superAdmin->id, 'name' => 'Omkar Root']);
    }

    #[Test]
    public function the_activity_log_on_the_dashboard_shows_the_mask(): void
    {
        activity()->causedBy($this->superAdmin)->log('did something sensitive');

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertStatus(200)
            ->assertSee('System Admin')
            ->assertDontSee('Omkar Root');
    }

    #[Test]
    public function a_super_admin_reading_the_same_log_sees_the_real_name(): void
    {
        activity()->causedBy($this->superAdmin)->log('did something sensitive');

        $this->actingAs($this->superAdmin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertStatus(200)
            ->assertSee('Omkar Root');
    }

    #[Test]
    public function unknown_and_null_actors_fall_back_gracefully(): void
    {
        $this->actingAs($this->admin, 'admin');

        $this->assertSame('System', SuperAdminMask::label(null));
        $this->assertSame('HR', SuperAdminMask::label(null, 'HR'));
        // An Employee causer is printed as themselves, never masked.
        $this->assertSame('Asha Verma', SuperAdminMask::label($this->employee));
    }

    #[Test]
    public function the_role_name_itself_is_relabelled(): void
    {
        $this->actingAs($this->admin, 'admin');
        $this->assertSame('System Admin', SuperAdminMask::roleLabel('Super Admin'));
        $this->assertSame('HR Manager', SuperAdminMask::roleLabel('HR Manager'));

        $this->actingAs($this->superAdmin, 'admin');
        $this->assertSame('Super Admin', SuperAdminMask::roleLabel('Super Admin'));
    }

    // ═══ 2. Bulk payslip delete ══════════════════════════════════════════

    /**
     * Payslips are unique per (employee, month, year), so each fixture slip gets
     * its own employee — which is also closer to a real payroll run.
     */
    private function makePayslip(string $code, string $status = 'generated', ?Employee $employee = null): Payslip
    {
        $employee ??= $this->extraEmployee($code);

        return Payslip::create([
            'business_id' => $this->business->id,
            'payslip_code' => $code,
            'employee_id' => $employee->id,
            'month' => 9, 'year' => 2026,
            'period_start' => '2026-09-01', 'period_end' => '2026-09-30',
            'working_days' => 26, 'paid_days' => 26, 'lop_days' => 0,
            'basic' => 20000, 'gross_earnings' => 30000,
            'total_deductions' => 2000, 'net_pay' => 28000,
            'status' => $status,
        ]);
    }

    private function extraEmployee(string $suffix): Employee
    {
        return Employee::create([
            'business_id' => $this->business->id,
            'employee_code' => 'EMP-'.strtoupper(substr(md5($suffix), 0, 6)),
            'email' => strtolower(substr(md5($suffix), 0, 6)).'@modc.test',
            'first_name' => 'Staff', 'last_name' => $suffix,
            'status' => 'active', 'password' => 'secret', 'joining_date' => '2025-01-01',
        ]);
    }

    #[Test]
    public function a_generated_payslip_can_be_deleted(): void
    {
        $payslip = $this->makePayslip('PS-001');

        $result = app(PayrollService::class)->deletePayslips([$payslip]);

        $this->assertSame(1, $result['deleted']);
        $this->assertSame(0, Payslip::count());
    }

    #[Test]
    public function a_paid_payslip_is_protected_without_an_override(): void
    {
        $payslip = $this->makePayslip('PS-PAID', 'paid');

        $result = app(PayrollService::class)->deletePayslips([$payslip]);

        $this->assertSame(0, $result['deleted']);
        $this->assertSame(1, $result['skipped_paid']);
        $this->assertSame(1, Payslip::count());
    }

    #[Test]
    public function a_paid_payslip_is_removed_with_the_override(): void
    {
        $payslip = $this->makePayslip('PS-PAID', 'paid');

        $result = app(PayrollService::class)->deletePayslips([$payslip], overridePaid: true);

        $this->assertSame(1, $result['deleted']);
        $this->assertSame(0, Payslip::count());
    }

    #[Test]
    public function deleting_a_payslip_returns_its_penalties_and_adjustments_for_the_next_run(): void
    {
        $payslip = $this->makePayslip('PS-002', 'generated', $this->employee);

        $type = PenaltyType::create([
            'business_id' => $this->business->id, 'name' => 'Late Mark', 'amount' => 500, 'status' => 'active',
        ]);
        $penalty = Penalty::create([
            'business_id' => $this->business->id, 'penalty_code' => 'PEN-1',
            'employee_id' => $this->employee->id, 'penalty_type_id' => $type->id,
            'amount' => 500, 'original_amount' => 500, 'incident_date' => '2026-09-02',
            'status' => 'deducted', 'payslip_id' => $payslip->id,
        ]);
        $adjustment = PayrollAdjustment::create([
            'business_id' => $this->business->id, 'employee_id' => $this->employee->id,
            'month' => 9, 'year' => 2026, 'component' => 'incentive', 'amount' => 1000,
            'applied' => true, 'payslip_id' => $payslip->id,
        ]);

        $result = app(PayrollService::class)->deletePayslips([$payslip]);

        $this->assertSame(1, $result['released_penalties']);
        $this->assertSame(1, $result['released_adjustments']);

        // Without this the generator would never pick them up again: it only
        // reads penalties with a null payslip_id and adjustments not applied.
        $penalty->refresh();
        $this->assertSame('pending', $penalty->status);
        $this->assertNull($penalty->payslip_id);

        $adjustment->refresh();
        $this->assertFalse((bool) $adjustment->applied);
        $this->assertNull($adjustment->payslip_id);
    }

    #[Test]
    public function bulk_delete_removes_the_ticked_rows_and_keeps_paid_ones(): void
    {
        $a = $this->makePayslip('PS-A');
        $b = $this->makePayslip('PS-B');
        $paid = $this->makePayslip('PS-C', 'paid');

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.hr.payroll.bulk-destroy'), [
                'month' => 9, 'year' => 2026,
                'ids' => [$a->id, $b->id, $paid->id],
            ])
            ->assertRedirect();

        $this->assertSame(1, Payslip::count());
        $this->assertSame('PS-C', Payslip::first()->payslip_code);
    }

    #[Test]
    public function bulk_delete_can_clear_a_whole_month_via_select_all(): void
    {
        $this->makePayslip('PS-A');
        $this->makePayslip('PS-B');
        $this->makePayslip('PS-C');

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.hr.payroll.bulk-destroy'), [
                'month' => 9, 'year' => 2026, 'select_all' => 1,
            ])
            ->assertRedirect();

        $this->assertSame(0, Payslip::count());
    }

    #[Test]
    public function select_all_respects_the_month_filter(): void
    {
        $this->makePayslip('PS-SEP');
        Payslip::create([
            'business_id' => $this->business->id, 'payslip_code' => 'PS-AUG',
            'employee_id' => $this->employee->id, 'month' => 8, 'year' => 2026,
            'period_start' => '2026-08-01', 'period_end' => '2026-08-31',
            'working_days' => 26, 'paid_days' => 26, 'lop_days' => 0,
            'basic' => 20000, 'gross_earnings' => 30000,
            'total_deductions' => 2000, 'net_pay' => 28000, 'status' => 'generated',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.hr.payroll.bulk-destroy'), [
                'month' => 9, 'year' => 2026, 'select_all' => 1,
            ])->assertRedirect();

        $this->assertSame(1, Payslip::count());
        $this->assertSame('PS-AUG', Payslip::first()->payslip_code);
    }

    #[Test]
    public function every_deletion_is_written_to_the_audit_log_with_the_count(): void
    {
        $this->makePayslip('PS-A');
        $this->makePayslip('PS-B');

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.hr.payroll.bulk-destroy'), [
                'month' => 9, 'year' => 2026, 'select_all' => 1,
            ])->assertRedirect();

        $entry = Activity::where('log_name', 'payroll')->latest()->first();

        $this->assertNotNull($entry);
        $this->assertSame('Deleted 2 payslip(s) for September 2026', $entry->description);
        $this->assertSame($this->admin->id, $entry->causer_id);
        $this->assertSame(2, $entry->properties['deleted']);
        $this->assertEqualsCanonicalizing(['PS-A', 'PS-B'], (array) $entry->properties['payslip_codes']);
        $this->assertNotNull($entry->created_at);
    }

    #[Test]
    public function an_admin_without_the_delete_permission_is_refused(): void
    {
        $hr = Admin::create([
            'name' => 'Sales Person', 'email' => 'sales@modc.test',
            'password' => bcrypt('password'), 'phone' => '9990003333',
            'status' => 'active', 'business_id' => $this->business->id,
        ]);
        $hr->assignRole('Sales');

        $payslip = $this->makePayslip('PS-A');

        $this->actingAs($hr, 'admin')
            ->post(route('admin.hr.payroll.bulk-destroy'), [
                'month' => 9, 'year' => 2026, 'ids' => [$payslip->id],
            ])
            ->assertStatus(403);

        $this->assertSame(1, Payslip::count());
    }

    #[Test]
    public function hr_may_clear_a_run_but_not_remove_a_paid_payslip(): void
    {
        $hr = Admin::create([
            'name' => 'HR Person', 'email' => 'hr@modc.test',
            'password' => bcrypt('password'), 'phone' => '9990004444',
            'status' => 'active', 'business_id' => $this->business->id,
        ]);
        $hr->assignRole('HR Manager');

        $this->assertTrue($hr->can('payroll.delete'));
        $this->assertFalse($hr->can('payroll.delete_paid'));

        $paid = $this->makePayslip('PS-PAID', 'paid');

        // Even if the override is posted, it is ignored without the permission.
        $this->actingAs($hr, 'admin')
            ->post(route('admin.hr.payroll.bulk-destroy'), [
                'month' => 9, 'year' => 2026, 'ids' => [$paid->id], 'override_paid' => 1,
            ])->assertRedirect();

        $this->assertSame(1, Payslip::count());
    }

    #[Test]
    public function deleting_nothing_reports_it_rather_than_failing_silently(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.hr.payroll.bulk-destroy'), ['month' => 9, 'year' => 2026])
            ->assertRedirect()
            ->assertSessionHas('warning');
    }

    // ═══ 3. Combined Leave ═══════════════════════════════════════════════

    private function leaveType(string $code, string $name, bool $paid = true): LeaveType
    {
        return LeaveType::create([
            'business_id' => $this->business->id, 'code' => $code, 'name' => $name,
            'annual_quota' => 12, 'is_paid' => $paid, 'status' => 'active',
        ]);
    }

    private function balance(LeaveType $type, float $allocated): LeaveBalance
    {
        return LeaveBalance::create([
            'business_id' => $this->business->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $type->id,
            'year' => 2026,
            'allocated' => $allocated, 'used' => 0, 'pending' => 0, 'carried_forward' => 0,
        ]);
    }

    private function submitCombined(array $splits, ?string $to = null): LeaveRequest
    {
        return app(LeaveService::class)->submit([
            'employee_id' => $this->employee->id,
            'from_date' => now()->toDateString(),
            'to_date' => $to ?? now()->toDateString(),
            'day_portion' => 'full',
            'reason' => 'Combining fractional balances',
            'splits' => $splits,
        ]);
    }

    #[Test]
    public function half_casual_plus_half_sick_makes_one_full_day(): void
    {
        $cl = $this->leaveType('CL', 'Casual Leave');
        $sl = $this->leaveType('SL', 'Sick Leave');
        $this->balance($cl, 0.5);
        $this->balance($sl, 0.5);

        $request = $this->submitCombined([
            ['leave_type_id' => $cl->id, 'days' => 0.5],
            ['leave_type_id' => $sl->id, 'days' => 0.5],
        ]);

        $this->assertTrue($request->is_combined);
        $this->assertSame(1.0, (float) $request->days);
        $this->assertCount(2, $request->splits);
        $this->assertSame('0.5 Casual Leave + 0.5 Sick Leave', $request->fresh()->load('splits.leaveType')->split_label);

        // Each bucket holds its own half while the request is pending.
        $this->assertSame('0.50', (string) $cl->balances()->first()->pending);
        $this->assertSame('0.50', (string) $sl->balances()->first()->pending);
    }

    #[Test]
    public function neither_type_alone_could_have_funded_the_day(): void
    {
        $cl = $this->leaveType('CL', 'Casual Leave');
        $sl = $this->leaveType('SL', 'Sick Leave');
        $this->balance($cl, 0.5);
        $this->balance($sl, 0.5);

        // A single-type request for the same day is refused by the balance gate…
        try {
            app(LeaveService::class)->submit([
                'employee_id' => $this->employee->id, 'leave_type_id' => $cl->id,
                'from_date' => now()->toDateString(), 'to_date' => now()->toDateString(),
                'day_portion' => 'full', 'reason' => 'Single type attempt',
            ]);
            $this->fail('Expected the single-type request to be refused.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('0.5 day(s) of Casual Leave', $e->getMessage());
        }

        // …but combining the two halves succeeds. That is the whole feature.
        $this->assertTrue($this->submitCombined([
            ['leave_type_id' => $cl->id, 'days' => 0.5],
            ['leave_type_id' => $sl->id, 'days' => 0.5],
        ])->is_combined);
    }

    #[Test]
    public function the_primary_type_is_the_largest_contributor(): void
    {
        $cl = $this->leaveType('CL', 'Casual Leave');
        $sl = $this->leaveType('SL', 'Sick Leave');
        $this->balance($cl, 5);
        $this->balance($sl, 5);

        // 2 days: 0.5 CL + 1.5 SL → SL is primary.
        $request = $this->submitCombined([
            ['leave_type_id' => $cl->id, 'days' => 0.5],
            ['leave_type_id' => $sl->id, 'days' => 1.5],
        ], now()->addDay()->toDateString());

        $this->assertSame($sl->id, $request->leave_type_id);
    }

    #[Test]
    public function a_split_that_does_not_add_up_is_refused(): void
    {
        $cl = $this->leaveType('CL', 'Casual Leave');
        $sl = $this->leaveType('SL', 'Sick Leave');
        $this->balance($cl, 5);
        $this->balance($sl, 5);

        try {
            $this->submitCombined([
                ['leave_type_id' => $cl->id, 'days' => 0.5],
                ['leave_type_id' => $sl->id, 'days' => 0.5],
            ], now()->addDay()->toDateString());   // 2 days requested, 1 split
            $this->fail('Expected a mismatch to be refused.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('must add up to the 2 day(s)', $e->getMessage());
        }

        $this->assertSame(0, LeaveRequest::count());
    }

    #[Test]
    public function the_same_type_cannot_appear_twice(): void
    {
        $cl = $this->leaveType('CL', 'Casual Leave');
        $this->balance($cl, 5);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Each leave type can only appear once');

        $this->submitCombined([
            ['leave_type_id' => $cl->id, 'days' => 0.5],
            ['leave_type_id' => $cl->id, 'days' => 0.5],
        ]);
    }

    #[Test]
    public function the_balance_gate_applies_to_every_contributing_type(): void
    {
        $cl = $this->leaveType('CL', 'Casual Leave');
        $sl = $this->leaveType('SL', 'Sick Leave');
        $this->balance($cl, 0.5);
        $this->balance($sl, 0);        // nothing left in the second bucket

        try {
            $this->submitCombined([
                ['leave_type_id' => $cl->id, 'days' => 0.5],
                ['leave_type_id' => $sl->id, 'days' => 0.5],
            ]);
            $this->fail('Expected the empty second bucket to refuse the request.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('No Sick Leave remains', $e->getMessage());
        }

        $this->assertSame(0, LeaveRequest::count());
    }

    #[Test]
    public function only_one_type_is_stored_as_an_ordinary_request(): void
    {
        $cl = $this->leaveType('CL', 'Casual Leave');
        $this->balance($cl, 5);

        // Nothing to combine — a single-row "split" degrades to a normal request
        // so every existing screen keeps working.
        $request = $this->submitCombined([['leave_type_id' => $cl->id, 'days' => 1]]);

        $this->assertFalse($request->is_combined);
        $this->assertSame($cl->id, $request->leave_type_id);
        $this->assertCount(0, $request->splits);
    }

    #[Test]
    public function approving_deducts_correctly_from_both_balances(): void
    {
        $cl = $this->leaveType('CL', 'Casual Leave');
        $sl = $this->leaveType('SL', 'Sick Leave');
        $this->balance($cl, 0.5);
        $this->balance($sl, 0.5);

        $request = $this->submitCombined([
            ['leave_type_id' => $cl->id, 'days' => 0.5],
            ['leave_type_id' => $sl->id, 'days' => 0.5],
        ]);

        app(LeaveService::class)->approve($request, $this->admin->id, 'Approved');

        $clBalance = $cl->balances()->first();
        $slBalance = $sl->balances()->first();

        $this->assertSame('0.50', (string) $clBalance->used);
        $this->assertSame('0.50', (string) $slBalance->used);
        $this->assertSame('0.00', (string) $clBalance->pending);
        $this->assertSame('0.00', (string) $slBalance->pending);

        // And the split rows record what each type funded.
        foreach ($request->fresh()->splits as $split) {
            $this->assertSame('0.5', (string) $split->paid_days);
            $this->assertSame('0.0', (string) $split->unpaid_days);
        }
    }

    #[Test]
    public function a_partly_unpaid_approval_shares_the_paid_days_across_types(): void
    {
        $cl = $this->leaveType('CL', 'Casual Leave');
        $sl = $this->leaveType('SL', 'Sick Leave');
        $this->balance($cl, 5);
        $this->balance($sl, 5);

        // 2 days combined 1 + 1, HR approves only 1 day as paid.
        $request = $this->submitCombined([
            ['leave_type_id' => $cl->id, 'days' => 1],
            ['leave_type_id' => $sl->id, 'days' => 1],
        ], now()->addDay()->toDateString());

        app(LeaveService::class)->approve($request, $this->admin->id, null, 1.0);

        $request->refresh()->load('splits');

        $this->assertSame(1.0, (float) $request->paid_days);
        $this->assertSame(1.0, (float) $request->unpaid_days);
        // Paid days add up to exactly what was approved — no rounding drift.
        $this->assertSame(1.0, round($request->splits->sum(fn ($s) => (float) $s->paid_days), 1));
        $this->assertSame(1.0, round($request->splits->sum(fn ($s) => (float) $s->unpaid_days), 1));
    }

    #[Test]
    public function rejecting_releases_the_hold_on_every_type(): void
    {
        $cl = $this->leaveType('CL', 'Casual Leave');
        $sl = $this->leaveType('SL', 'Sick Leave');
        $this->balance($cl, 0.5);
        $this->balance($sl, 0.5);

        $request = $this->submitCombined([
            ['leave_type_id' => $cl->id, 'days' => 0.5],
            ['leave_type_id' => $sl->id, 'days' => 0.5],
        ]);

        app(LeaveService::class)->reject($request, $this->admin->id, 'Not this week');

        $this->assertSame('0.00', (string) $cl->balances()->first()->pending);
        $this->assertSame('0.00', (string) $sl->balances()->first()->pending);
        $this->assertSame('0.00', (string) $cl->balances()->first()->used);
    }

    #[Test]
    public function an_approved_combined_request_can_no_longer_be_cancelled(): void
    {
        // This used to credit both halves back. Employees were using it to
        // reclaim balance for leave they had actually taken, including from
        // closed months, so an approved request is now a settled decision that
        // only HR can reverse.
        $cl = $this->leaveType('CL', 'Casual Leave');
        $sl = $this->leaveType('SL', 'Sick Leave');
        $this->balance($cl, 0.5);
        $this->balance($sl, 0.5);

        $request = $this->submitCombined([
            ['leave_type_id' => $cl->id, 'days' => 0.5],
            ['leave_type_id' => $sl->id, 'days' => 0.5],
        ]);

        $service = app(LeaveService::class);
        $service->approve($request, $this->admin->id);

        $this->expectException(\RuntimeException::class);

        try {
            $service->cancel($request->refresh());
        } finally {
            // Both buckets stay consumed, and the request stays approved.
            $this->assertSame('approved', $request->fresh()->status);
            $this->assertSame('0.50', (string) $cl->balances()->first()->used);
            $this->assertSame('0.50', (string) $sl->balances()->first()->used);
        }
    }

    #[Test]
    public function cancelling_a_pending_combined_request_releases_both_holds(): void
    {
        $cl = $this->leaveType('CL', 'Casual Leave');
        $sl = $this->leaveType('SL', 'Sick Leave');
        $this->balance($cl, 0.5);
        $this->balance($sl, 0.5);

        $request = $this->submitCombined([
            ['leave_type_id' => $cl->id, 'days' => 0.5],
            ['leave_type_id' => $sl->id, 'days' => 0.5],
        ]);

        app(LeaveService::class)->cancel($request);

        // Every contributing type releases its hold, not just the primary one.
        $this->assertSame('cancelled', $request->fresh()->status);
        $this->assertSame('0.00', (string) $cl->balances()->first()->pending);
        $this->assertSame('0.00', (string) $sl->balances()->first()->pending);
    }

    #[Test]
    public function the_employee_portal_can_submit_a_combined_request(): void
    {
        $cl = $this->leaveType('CL', 'Casual Leave');
        $sl = $this->leaveType('SL', 'Sick Leave');
        $this->balance($cl, 0.5);
        $this->balance($sl, 0.5);

        $this->actingAs($this->employee, 'employee')
            ->post(route('employee.leaves.store'), [
                'from_date' => now()->toDateString(),
                'to_date' => now()->toDateString(),
                'day_portion' => 'full',
                'reason' => 'Half casual plus half sick',
                'splits' => [
                    ['leave_type_id' => $cl->id, 'days' => 0.5],
                    ['leave_type_id' => $sl->id, 'days' => 0.5],
                ],
            ])
            ->assertRedirect(route('employee.leaves.index'))
            ->assertSessionHas('success');

        $request = LeaveRequest::first();
        $this->assertNotNull($request);
        $this->assertTrue($request->is_combined);
        $this->assertCount(2, $request->splits);
    }

    #[Test]
    public function the_split_is_shown_on_the_request_and_the_approval_screen(): void
    {
        $cl = $this->leaveType('CL', 'Casual Leave');
        $sl = $this->leaveType('SL', 'Sick Leave');
        $this->balance($cl, 0.5);
        $this->balance($sl, 0.5);

        $request = $this->submitCombined([
            ['leave_type_id' => $cl->id, 'days' => 0.5],
            ['leave_type_id' => $sl->id, 'days' => 0.5],
        ]);

        $this->actingAs($this->employee, 'employee')
            ->get(route('employee.leaves.show', $request))
            ->assertStatus(200)
            ->assertSee('COMBINED')
            ->assertSee('0.5 Casual Leave + 0.5 Sick Leave');

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.hr.leaves.show', $request))
            ->assertStatus(200)
            ->assertSee('COMBINED')
            ->assertSee('0.5 Casual Leave + 0.5 Sick Leave');
    }

    // ── Employee-portal payslip deletion is admin-only ───────────────────

    #[Test]
    public function an_employee_cannot_delete_their_own_payslips(): void
    {
        // The portal lists these rows, but they are the employer's wage record
        // too — the statutory registers read them. Only the admin guard may
        // remove one, and a plain employee session is not that.
        $slip = $this->makePayslip('PS-EMP-1', 'generated', $this->employee);

        $this->actingAs($this->employee, 'employee')
            ->delete(route('employee.payslips.bulk-destroy'), ['ids' => [$slip->id]])
            ->assertStatus(403);

        $this->assertDatabaseHas('payslips', ['id' => $slip->id]);
    }

    #[Test]
    public function the_employee_portal_shows_no_delete_control_to_an_employee(): void
    {
        $this->makePayslip('PS-EMP-2', 'generated', $this->employee);

        $this->actingAs($this->employee, 'employee')
            ->get(route('employee.payslips.index'))
            ->assertOk()
            ->assertDontSee('Delete selected')
            ->assertDontSee('name="ids[]"', false);
    }

    #[Test]
    public function an_admin_signed_in_alongside_can_delete_from_the_portal(): void
    {
        $slip = $this->makePayslip('PS-EMP-3', 'generated', $this->employee);

        $this->actingAs($this->employee, 'employee')
            ->actingAs($this->admin, 'admin')
            ->delete(route('employee.payslips.bulk-destroy'), ['ids' => [$slip->id]])
            ->assertRedirect();

        $this->assertDatabaseMissing('payslips', ['id' => $slip->id]);
    }

    #[Test]
    public function deleting_from_the_portal_only_touches_that_employees_slips(): void
    {
        $mine = $this->makePayslip('PS-EMP-4', 'generated', $this->employee);
        $someoneElse = $this->makePayslip('PS-OTHER-4');

        $this->actingAs($this->employee, 'employee')
            ->actingAs($this->admin, 'admin')
            ->delete(route('employee.payslips.bulk-destroy'), [
                'ids' => [$mine->id, $someoneElse->id],
            ])->assertRedirect();

        $this->assertDatabaseMissing('payslips', ['id' => $mine->id]);
        $this->assertDatabaseHas('payslips', ['id' => $someoneElse->id]);
    }

    #[Test]
    public function a_paid_payslip_survives_a_portal_delete_without_the_override(): void
    {
        // Deleting through PayrollService means the paid-payslip protection
        // applies here too, rather than the portal being a way around it.
        $this->admin->roles->first()->revokePermissionTo('payroll.delete_paid');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $slip = $this->makePayslip('PS-EMP-5', 'paid', $this->employee);

        $this->actingAs($this->employee, 'employee')
            ->actingAs($this->admin, 'admin')
            ->delete(route('employee.payslips.bulk-destroy'), ['ids' => [$slip->id]]);

        $this->assertDatabaseHas('payslips', ['id' => $slip->id]);
    }

    #[Test]
    public function a_portal_delete_releases_the_penalties_the_slip_consumed(): void
    {
        // The slip must go through PayrollService, not a plain delete: a raw
        // delete would leave its penalties pointing at a row that is gone.
        $slip = $this->makePayslip('PS-EMP-6', 'generated', $this->employee);

        $type = PenaltyType::create([
            'business_id' => $this->business->id, 'name' => 'Late Mark', 'amount' => 100, 'status' => 'active',
        ]);
        $penalty = Penalty::create([
            'business_id' => $this->business->id, 'penalty_code' => 'PEN-PORTAL-1',
            'employee_id' => $this->employee->id, 'penalty_type_id' => $type->id,
            'amount' => 100, 'original_amount' => 100, 'incident_date' => '2026-09-10',
            'status' => 'deducted', 'payslip_id' => $slip->id,
        ]);

        $this->actingAs($this->employee, 'employee')
            ->actingAs($this->admin, 'admin')
            ->delete(route('employee.payslips.bulk-destroy'), ['ids' => [$slip->id]])
            ->assertRedirect();

        $penalty->refresh();
        $this->assertSame('pending', $penalty->status);
        $this->assertNull($penalty->payslip_id);
    }
}
