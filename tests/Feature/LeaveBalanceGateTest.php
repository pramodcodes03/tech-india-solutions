<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Business;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\LeaveBalanceGateService;
use App\Services\LeaveService;
use App\Support\HrSettings;
use App\Support\Tenancy\CurrentBusiness;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUsers;

/**
 * Module C — the Leave Balance Gate and its Leave Without Pay exception.
 *
 * Policy under test:
 *   gate on  + LWP exception on  (the shipped default) → paid types blocked when
 *       over balance, unpaid LWP stays open.
 *   gate on  + LWP exception off → the block is absolute; once every paid bucket
 *       is exhausted, even LWP is refused.
 *   gate off → the previous behaviour: over-balance requests go through and HR
 *       splits them paid/unpaid at approval.
 */
class LeaveBalanceGateTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    protected Business $business;

    protected Employee $employee;

    protected LeaveType $paidType;

    protected LeaveType $lwpType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();

        // A Monday, so a single-day request is one leave day — the default
        // week-off pattern (no rows configured) is Sunday.
        Carbon::setTestNow(Carbon::parse('2026-09-07 10:00:00'));

        $this->business = Business::create([
            'name' => 'Gate Co', 'slug' => 'gate-co',
            'currency_code' => 'INR', 'currency_symbol' => '₹',
        ]);
        app(CurrentBusiness::class)->set($this->business);

        $this->employee = Employee::create([
            'business_id' => $this->business->id, 'employee_code' => 'EMP-001',
            'email' => 'asha@gate.test', 'first_name' => 'Asha', 'last_name' => 'Verma',
            'status' => 'active', 'password' => 'secret', 'joining_date' => '2025-01-01',
        ]);

        $this->paidType = LeaveType::create([
            'business_id' => $this->business->id, 'code' => 'CL', 'name' => 'Casual Leave',
            'annual_quota' => 12, 'is_paid' => true, 'status' => 'active',
        ]);

        $this->lwpType = LeaveType::create([
            'business_id' => $this->business->id, 'code' => 'LWP', 'name' => 'Leave Without Pay',
            'annual_quota' => 0, 'is_paid' => false, 'status' => 'active',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function giveBalance(float $allocated, ?LeaveType $type = null): LeaveBalance
    {
        return LeaveBalance::create([
            'business_id' => $this->business->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => ($type ?? $this->paidType)->id,
            'year' => (int) now()->format('Y'),
            'allocated' => $allocated, 'used' => 0, 'pending' => 0, 'carried_forward' => 0,
        ]);
    }

    /** Apply for $days consecutive weekdays starting today (a Monday). */
    private function apply(LeaveType $type, int $days = 1): LeaveRequest
    {
        return app(LeaveService::class)->submit([
            'employee_id' => $this->employee->id,
            'leave_type_id' => $type->id,
            'from_date' => now()->toDateString(),
            'to_date' => now()->addDays($days - 1)->toDateString(),
            'day_portion' => 'full',
            'reason' => 'Testing the balance gate',
        ]);
    }

    private function setGate(bool $enabled, bool $lwpException): void
    {
        HrSettings::setForBusiness('leave_balance_gate_enabled', $this->business->id, $enabled ? 1 : 0, 'leave');
        HrSettings::setForBusiness('leave_lwp_exception_enabled', $this->business->id, $lwpException ? 1 : 0, 'leave');
    }

    // ── Defaults ─────────────────────────────────────────────────────────

    #[Test]
    public function the_gate_ships_on_with_the_lwp_exception_allowed(): void
    {
        $gate = app(LeaveBalanceGateService::class);

        $this->assertTrue($gate->isEnabled($this->business->id), 'The gate should be on out of the box.');
        $this->assertTrue($gate->lwpExceptionAllowed($this->business->id), 'LWP should be allowed by default.');
    }

    // ── Gate on, paid types ──────────────────────────────────────────────

    #[Test]
    public function a_request_within_balance_is_accepted(): void
    {
        $this->giveBalance(5);

        $request = $this->apply($this->paidType, 3);

        $this->assertSame('pending', $request->status);
        $this->assertSame(3.0, (float) $request->days);
        // The 3 days are held against the balance while HR reviews.
        $this->assertSame('3.00', (string) $this->paidType->balances()->first()->pending);
    }

    #[Test]
    public function a_request_for_exactly_the_remaining_balance_is_accepted(): void
    {
        $this->giveBalance(2);

        $request = $this->apply($this->paidType, 2);

        $this->assertSame('pending', $request->status);
    }

    #[Test]
    public function a_request_over_the_balance_is_refused(): void
    {
        $this->giveBalance(2);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('You have 2 day(s) of Casual Leave available but applied for 3.');

        $this->apply($this->paidType, 3);
    }

    #[Test]
    public function a_refused_request_is_not_written_at_all(): void
    {
        $this->giveBalance(1);

        try {
            $this->apply($this->paidType, 4);
        } catch (\RuntimeException) {
            // expected
        }

        $this->assertSame(0, LeaveRequest::count(), 'No request row should survive the refusal.');
        $this->assertSame('0.00', (string) $this->paidType->balances()->first()->pending,
            'No pending days should be held for a refused request.');
    }

    #[Test]
    public function a_request_with_no_balance_at_all_is_refused_and_points_at_lwp(): void
    {
        $this->giveBalance(0);

        try {
            $this->apply($this->paidType, 1);
            $this->fail('Expected the gate to refuse the request.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('No Casual Leave remains', $e->getMessage());
            $this->assertStringContainsString('Leave Without Pay', $e->getMessage());
        }
    }

    #[Test]
    public function an_employee_with_no_balance_row_is_treated_as_zero(): void
    {
        // No LeaveBalance row exists for this employee/type at all.
        $this->expectException(\RuntimeException::class);

        $this->apply($this->paidType, 1);
    }

    #[Test]
    public function days_already_used_and_pending_count_against_the_balance(): void
    {
        $balance = $this->giveBalance(5);
        $balance->update(['used' => 2, 'pending' => 2]);   // 1 day genuinely left

        $this->assertSame(1.0, app(LeaveBalanceGateService::class)
            ->availableFor($this->employee->id, $this->paidType->id, (int) now()->format('Y')));

        $this->expectException(\RuntimeException::class);
        $this->apply($this->paidType, 2);
    }

    // ── The LWP exception ────────────────────────────────────────────────

    #[Test]
    public function lwp_stays_open_when_the_exception_is_allowed(): void
    {
        $this->setGate(enabled: true, lwpException: true);
        $this->giveBalance(0);

        $request = $this->apply($this->lwpType, 3);

        $this->assertSame('pending', $request->status);
        $this->assertSame(3.0, (float) $request->days);
    }

    #[Test]
    public function lwp_is_refused_when_the_block_is_absolute(): void
    {
        $this->setGate(enabled: true, lwpException: false);
        $this->giveBalance(0);

        try {
            $this->apply($this->lwpType, 1);
            $this->fail('Expected the absolute block to refuse the LWP request.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('not permitted as an exception', $e->getMessage());
        }
    }

    #[Test]
    public function lwp_is_still_allowed_under_an_absolute_block_while_paid_balance_remains(): void
    {
        $this->setGate(enabled: true, lwpException: false);
        $this->giveBalance(4);   // still has paid leave in hand

        $request = $this->apply($this->lwpType, 1);

        $this->assertSame('pending', $request->status);
    }

    #[Test]
    public function a_paid_request_is_refused_the_same_way_under_an_absolute_block(): void
    {
        $this->setGate(enabled: true, lwpException: false);
        $this->giveBalance(1);

        try {
            $this->apply($this->paidType, 2);
            $this->fail('Expected the gate to refuse the request.');
        } catch (\RuntimeException $e) {
            // With no exception on offer, the message must not send them to LWP.
            $this->assertStringContainsString('Please contact HR', $e->getMessage());
            $this->assertStringNotContainsString('apply under a Leave Without Pay type', $e->getMessage());
        }
    }

    // ── Gate off ─────────────────────────────────────────────────────────

    #[Test]
    public function turning_the_gate_off_restores_the_previous_behaviour(): void
    {
        $this->setGate(enabled: false, lwpException: true);
        $this->giveBalance(1);

        $request = $this->apply($this->paidType, 3);

        $this->assertSame('pending', $request->status);
        $this->assertSame(3.0, (float) $request->days);
        // Only what the balance can fund is held; HR splits the rest at approval.
        $this->assertSame('1.00', (string) $this->paidType->balances()->first()->pending);
    }

    #[Test]
    public function with_the_gate_off_a_zero_balance_request_still_goes_through(): void
    {
        $this->setGate(enabled: false, lwpException: false);
        $this->giveBalance(0);

        $this->assertSame('pending', $this->apply($this->paidType, 2)->status);
    }

    // ── Per business ─────────────────────────────────────────────────────

    #[Test]
    public function the_setting_is_scoped_to_one_business(): void
    {
        $other = Business::create([
            'name' => 'Other Co', 'slug' => 'other-gate-co',
            'currency_code' => 'INR', 'currency_symbol' => '₹',
        ]);

        $this->setGate(enabled: false, lwpException: false);

        $gate = app(LeaveBalanceGateService::class);
        $this->assertFalse($gate->isEnabled($this->business->id));
        $this->assertTrue($gate->isEnabled($other->id), 'The other business keeps the default.');
    }

    // ── End to end through the employee portal ───────────────────────────

    #[Test]
    public function the_employee_portal_shows_the_refusal_instead_of_creating_a_request(): void
    {
        $this->giveBalance(1);

        $this->actingAs($this->employee, 'employee')
            ->post(route('employee.leaves.store'), [
                'leave_type_id' => $this->paidType->id,
                'from_date' => now()->toDateString(),
                'to_date' => now()->addDays(2)->toDateString(),
                'day_portion' => 'full',
                'reason' => 'Family function',
            ])
            ->assertRedirect()
            ->assertSessionHas('error', fn ($msg) => str_contains($msg, 'cannot be submitted'));

        $this->assertSame(0, LeaveRequest::count());
    }

    #[Test]
    public function the_apply_form_loads_and_states_the_policy_in_force(): void
    {
        $this->giveBalance(3);

        $this->actingAs($this->employee, 'employee')
            ->get(route('employee.leaves.create'))
            ->assertStatus(200)
            ->assertSee('Balance policy:')
            ->assertSee('apply under a Leave Without Pay type instead.', false);
    }

    // ── The settings screen ──────────────────────────────────────────────

    #[Test]
    public function hr_can_switch_both_toggles_from_leave_settings(): void
    {
        $admin = Admin::create([
            'name' => 'Admin', 'email' => 'admin@gate.test',
            'password' => bcrypt('password'), 'phone' => '9990001111',
            'status' => 'active', 'business_id' => $this->business->id,
        ]);
        $admin->assignRole('Admin');

        $base = [
            'probation_period_days' => 90,
            'leave_application_window_hours' => 72,
            'attendance_correction_tat_hours' => 48,
            'leave_accrual_frequency' => 'monthly',
            'leave_accrual_day' => 11,
            'el_working_days_required' => 240,
            'cl_sl_working_days_required' => 90,
            'el_carry_forward_cap' => 30,
            'full_day_hours' => 9,
            'half_day_hours' => 4.5,
        ];

        $this->actingAs($admin, 'admin')
            ->get(route('admin.hr.leave-settings.index'))
            ->assertStatus(200)
            ->assertSee('Leave Balance Gate');

        // Both ticked.
        $this->actingAs($admin, 'admin')->post(
            route('admin.hr.leave-settings.update'),
            $base + ['leave_balance_gate_enabled' => 1, 'leave_lwp_exception_enabled' => 1],
        )->assertRedirect();

        $gate = app(LeaveBalanceGateService::class);
        $this->assertTrue($gate->isEnabled($this->business->id));
        $this->assertTrue($gate->lwpExceptionAllowed($this->business->id));

        // Both unticked — a checkbox that is off posts nothing at all, which is
        // the case that would silently keep the old value if mishandled.
        $this->actingAs($admin, 'admin')->post(
            route('admin.hr.leave-settings.update'),
            $base,
        )->assertRedirect();

        $this->assertFalse($gate->isEnabled($this->business->id));
        $this->assertFalse($gate->lwpExceptionAllowed($this->business->id));
    }
}
