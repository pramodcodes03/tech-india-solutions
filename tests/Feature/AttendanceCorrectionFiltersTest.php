<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Attendance;
use App\Models\AttendanceRegularization;
use App\Models\Business;
use App\Models\Employee;
use App\Models\Shift;
use App\Support\Tenancy\CurrentBusiness;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUsers;

/**
 * The Shift and Date filters on the Attendance Corrections queue.
 *
 * Both filter on something that is not a column of the request itself — the
 * shift comes through the employee relation, and the date has to survive
 * alongside the other filters and pagination — so each one is asserted to
 * both keep what it should and drop what it should.
 */
class AttendanceCorrectionFiltersTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    private Business $business;

    private Admin $admin;

    private Employee $onDay;

    private Employee $onNight;

    private Employee $noShift;

    private AttendanceRegularization $dayRequest;

    private AttendanceRegularization $nightRequest;

    private AttendanceRegularization $noShiftRequest;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();

        $this->business = Business::create([
            'name' => 'Test Co', 'slug' => 'test-co',
            'currency_code' => 'INR', 'currency_symbol' => '₹',
        ]);
        app(CurrentBusiness::class)->set($this->business);

        $this->admin = Admin::create([
            'name' => 'Admin', 'email' => 'admin@test.com',
            'password' => bcrypt('password'), 'phone' => '9990001111',
            'status' => 'active', 'business_id' => $this->business->id,
        ]);
        $this->admin->assignRole('Admin');

        $day = Shift::create([
            'business_id' => $this->business->id, 'name' => 'Day Shift',
            'start_time' => '09:00:00', 'end_time' => '18:00:00',
        ]);
        $night = Shift::create([
            'business_id' => $this->business->id, 'name' => 'Night Shift',
            'start_time' => '21:00:00', 'end_time' => '06:00:00',
        ]);

        $this->onDay = $this->employee('EMP-DAY', 'Dayana', $day->id);
        $this->onNight = $this->employee('EMP-NIGHT', 'Nita', $night->id);
        $this->noShift = $this->employee('EMP-NONE', 'Nora', null);

        $this->dayRequest = $this->correction($this->onDay, '2026-08-28');
        $this->nightRequest = $this->correction($this->onNight, '2026-08-29');
        $this->noShiftRequest = $this->correction($this->noShift, '2026-08-28');
    }

    private function employee(string $code, string $first, ?int $shiftId): Employee
    {
        return Employee::create([
            'business_id' => $this->business->id, 'employee_code' => $code,
            'email' => strtolower($first).'@test.com', 'first_name' => $first,
            'status' => 'active', 'password' => 'secret',
            'joining_date' => '2025-01-01', 'shift_id' => $shiftId,
        ]);
    }

    private function correction(Employee $employee, string $date): AttendanceRegularization
    {
        return AttendanceRegularization::create([
            'business_id' => $this->business->id,
            'employee_id' => $employee->id,
            'date' => $date,
            'request_type' => 'missed_punch',
            'reason' => 'Forgot to punch out',
            'status' => 'pending',
        ]);
    }

    private function index(array $params = [])
    {
        return $this->actingAs($this->admin, 'admin')
            ->get(route('admin.hr.regularizations.index', $params));
    }

    #[Test]
    public function unfiltered_queue_shows_every_correction(): void
    {
        $this->index()
            ->assertOk()
            ->assertSee('Dayana')
            ->assertSee('Nita')
            ->assertSee('Nora');
    }

    #[Test]
    public function shift_filter_keeps_only_that_shift(): void
    {
        $day = Shift::where('name', 'Day Shift')->firstOrFail();

        $this->index(['shift_id' => $day->id])
            ->assertOk()
            ->assertSee('Dayana')
            ->assertDontSee('Nita')
            ->assertDontSee('Nora');
    }

    #[Test]
    public function shift_filter_isolates_employees_with_no_shift(): void
    {
        // "No shift" is a value in the Shift column, so it has to be filterable
        // — it is the case HR chases most, since those rows are judged on total
        // hours rather than a shift window.
        $this->index(['shift_id' => 'none'])
            ->assertOk()
            ->assertSee('Nora')
            ->assertDontSee('Dayana')
            ->assertDontSee('Nita');
    }

    #[Test]
    public function a_from_and_to_pair_keeps_only_dates_inside_the_range(): void
    {
        // 29 Aug only: Nita's correction is on it, the other two are on the 28th.
        $this->index(['from' => '2026-08-29', 'to' => '2026-08-29'])
            ->assertOk()
            ->assertSee('Nita')
            ->assertDontSee('Dayana')
            ->assertDontSee('Nora');
    }

    #[Test]
    public function the_range_is_inclusive_of_both_ends(): void
    {
        $this->index(['from' => '2026-08-28', 'to' => '2026-08-29'])
            ->assertOk()
            ->assertSee('Dayana')
            ->assertSee('Nita')
            ->assertSee('Nora');
    }

    #[Test]
    public function from_alone_means_everything_since_that_date(): void
    {
        $this->index(['from' => '2026-08-29'])
            ->assertOk()
            ->assertSee('Nita')
            ->assertDontSee('Dayana')
            ->assertDontSee('Nora');
    }

    #[Test]
    public function to_alone_means_everything_up_to_that_date(): void
    {
        $this->index(['to' => '2026-08-28'])
            ->assertOk()
            ->assertSee('Dayana')
            ->assertSee('Nora')
            ->assertDontSee('Nita');
    }

    #[Test]
    public function a_backwards_range_matches_nothing_rather_than_silently_widening(): void
    {
        // Each bound applies on its own, so from > to is an empty window. It
        // must not quietly swap the two and return more than was asked for.
        $this->index(['from' => '2026-08-29', 'to' => '2026-08-28'])
            ->assertOk()
            ->assertDontSee('Dayana')
            ->assertDontSee('Nita')
            ->assertDontSee('Nora');
    }

    #[Test]
    public function shift_and_date_filters_combine(): void
    {
        // Both filters on 28 Aug: two corrections fall on that date, and only
        // one of them belongs to an employee with no shift.
        $this->index(['from' => '2026-08-28', 'to' => '2026-08-28', 'shift_id' => 'none'])
            ->assertOk()
            ->assertSee('Nora')
            ->assertDontSee('Dayana')
            ->assertDontSee('Nita');
    }

    #[Test]
    public function filters_combine_with_the_existing_status_filter(): void
    {
        $this->correction($this->onDay, '2026-08-30')->update(['status' => 'approved']);

        $this->index(['status' => 'approved', 'shift_id' => 'none'])
            ->assertOk()
            ->assertDontSee('Dayana')
            ->assertDontSee('Nora');
    }

    #[Test]
    public function an_empty_filter_value_does_not_filter_anything_out(): void
    {
        // The "All Shifts" option posts an empty string; it must behave as no
        // filter rather than matching shift_id = ''.
        $this->index(['shift_id' => '', 'from' => '', 'to' => ''])
            ->assertOk()
            ->assertSee('Dayana')
            ->assertSee('Nita')
            ->assertSee('Nora');
    }

    // ── Bulk delete ──────────────────────────────────────────────────────

    private function bulkDelete(array $payload)
    {
        return $this->actingAs($this->admin, 'admin')
            ->from(route('admin.hr.regularizations.index'))
            ->delete(route('admin.hr.regularizations.bulk-destroy'), $payload);
    }

    #[Test]
    public function bulk_delete_removes_every_selected_request(): void
    {
        $this->bulkDelete(['ids' => [$this->dayRequest->id, $this->nightRequest->id]])
            ->assertRedirect(route('admin.hr.regularizations.index'));

        $this->assertDatabaseMissing('attendance_regularizations', ['id' => $this->dayRequest->id]);
        $this->assertDatabaseMissing('attendance_regularizations', ['id' => $this->nightRequest->id]);
        // Anything not ticked must survive.
        $this->assertDatabaseHas('attendance_regularizations', ['id' => $this->noShiftRequest->id]);
    }

    #[Test]
    public function a_single_id_wins_over_any_ticked_boxes(): void
    {
        // The row Delete button posts into the same form as the checkboxes, so
        // pressing it must delete that row only — never the current selection.
        $this->bulkDelete([
            'single_id' => $this->nightRequest->id,
            'ids' => [$this->dayRequest->id, $this->noShiftRequest->id],
        ])->assertRedirect();

        $this->assertDatabaseMissing('attendance_regularizations', ['id' => $this->nightRequest->id]);
        $this->assertDatabaseHas('attendance_regularizations', ['id' => $this->dayRequest->id]);
        $this->assertDatabaseHas('attendance_regularizations', ['id' => $this->noShiftRequest->id]);
    }

    #[Test]
    public function bulk_delete_with_nothing_selected_is_rejected(): void
    {
        $this->bulkDelete(['ids' => []])->assertSessionHasErrors('ids');

        $this->assertDatabaseCount('attendance_regularizations', 3);
    }

    #[Test]
    public function bulk_delete_cannot_reach_another_businesses_requests(): void
    {
        $other = Business::create([
            'name' => 'Other Co', 'slug' => 'other-co',
            'currency_code' => 'INR', 'currency_symbol' => '₹',
        ]);
        $otherEmployee = Employee::create([
            'business_id' => $other->id, 'employee_code' => 'OTH-1',
            'email' => 'other@test.com', 'first_name' => 'Omar',
            'status' => 'active', 'password' => 'secret', 'joining_date' => '2025-01-01',
        ]);
        $foreign = AttendanceRegularization::withoutGlobalScopes()->create([
            'business_id' => $other->id, 'employee_id' => $otherEmployee->id,
            'date' => '2026-08-28', 'request_type' => 'missed_punch',
            'reason' => 'Not yours', 'status' => 'pending',
        ]);

        $this->bulkDelete(['ids' => [$foreign->id, $this->dayRequest->id]])->assertRedirect();

        // The tenant scope filters it out, so it is neither deleted nor counted.
        $this->assertDatabaseHas('attendance_regularizations', ['id' => $foreign->id]);
        $this->assertDatabaseMissing('attendance_regularizations', ['id' => $this->dayRequest->id]);
    }

    #[Test]
    public function bulk_delete_says_when_an_applied_correction_is_left_behind(): void
    {
        // Deleting an applied request does not roll back the attendance it
        // changed, so the confirmation message has to say so.
        $this->dayRequest->update(['applied' => true, 'status' => 'approved']);

        $this->bulkDelete(['ids' => [$this->dayRequest->id, $this->nightRequest->id]]);

        $this->assertStringContainsString('remains in place', session('success'));
    }

    #[Test]
    public function bulk_delete_requires_the_delete_permission(): void
    {
        // A purpose-made role holding view but not delete. Revoking on the user
        // would not do it — the permission comes from the role, and
        // revokePermissionTo only drops a directly-assigned one.
        $role = Role::firstOrCreate(['name' => 'Corrections Viewer', 'guard_name' => 'admin']);
        $role->syncPermissions(['attendance_corrections.view']);

        $viewer = Admin::create([
            'name' => 'Viewer', 'email' => 'hrviewer@test.com',
            'password' => bcrypt('password'), 'phone' => '9990002222',
            'status' => 'active', 'business_id' => $this->business->id,
        ]);
        $viewer->assignRole($role);

        $this->actingAs($viewer, 'admin')
            ->delete(route('admin.hr.regularizations.bulk-destroy'), ['ids' => [$this->dayRequest->id]])
            ->assertForbidden();

        $this->assertDatabaseHas('attendance_regularizations', ['id' => $this->dayRequest->id]);
    }

    // ── Cross-business review (the production 500) ───────────────────────

    #[Test]
    public function a_super_admin_can_open_a_correction_from_another_business(): void
    {
        // Route-model binding switches a Super Admin to the business a record
        // lives in. EnsureBusinessContext used to re-derive the business from
        // the session straight afterwards and overwrite that switch, so the
        // employee relation resolved against the wrong tenant and came back
        // null — a 500 on the review screen.
        $other = Business::create([
            'name' => 'Other Co', 'slug' => 'other-co',
            'currency_code' => 'INR', 'currency_symbol' => '₹',
        ]);
        $otherEmployee = Employee::create([
            'business_id' => $other->id, 'employee_code' => 'OTH-1',
            'email' => 'omar@test.com', 'first_name' => 'Omar',
            'status' => 'active', 'password' => 'secret', 'joining_date' => '2025-01-01',
        ]);
        $foreign = AttendanceRegularization::withoutGlobalScopes()->create([
            'business_id' => $other->id, 'employee_id' => $otherEmployee->id,
            'date' => '2026-08-28', 'request_type' => 'missed_punch',
            'reason' => 'Cross-business', 'status' => 'pending',
        ]);

        $super = $this->createSuperAdmin();

        $this->actingAs($super, 'admin')
            ->withSession(['business_id' => $this->business->id])
            ->get(route('admin.hr.regularizations.show', $foreign))
            ->assertOk()
            ->assertSee('Omar');
    }

    #[Test]
    public function a_regular_admin_cannot_open_another_businesses_correction(): void
    {
        // The Super Admin switch must not become a general escape hatch.
        $other = Business::create([
            'name' => 'Other Co 2', 'slug' => 'other-co-2',
            'currency_code' => 'INR', 'currency_symbol' => '₹',
        ]);
        $otherEmployee = Employee::create([
            'business_id' => $other->id, 'employee_code' => 'OTH-2',
            'email' => 'olga@test.com', 'first_name' => 'Olga',
            'status' => 'active', 'password' => 'secret', 'joining_date' => '2025-01-01',
        ]);
        $foreign = AttendanceRegularization::withoutGlobalScopes()->create([
            'business_id' => $other->id, 'employee_id' => $otherEmployee->id,
            'date' => '2026-08-28', 'request_type' => 'missed_punch',
            'reason' => 'Cross-business', 'status' => 'pending',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.hr.regularizations.show', $foreign))
            ->assertNotFound();
    }

    #[Test]
    public function the_review_screen_survives_an_employee_it_cannot_resolve(): void
    {
        // Belt and braces: whatever the tenant context, a missing employee must
        // degrade to a label rather than take the page down.
        $this->dayRequest->employee()->delete();

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.hr.regularizations.show', $this->dayRequest))
            ->assertOk()
            ->assertSee('Employee unavailable');
    }

    // ── Week-Off resolution (Priority 4) ─────────────────────────────────

    #[Test]
    public function hr_can_resolve_a_correction_as_a_week_off(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.hr.regularizations.approve', $this->dayRequest), [
                'resulting_status' => 'weekend',
                'review_remarks' => 'This was the employee week off.',
            ])->assertRedirect();

        $this->assertDatabaseHas('attendance', [
            'employee_id' => $this->onDay->id,
            'date' => '2026-08-28',
            'status' => 'weekend',
        ]);

        $this->assertSame('approved', $this->dayRequest->fresh()->status);
    }

    #[Test]
    public function the_week_off_resolution_reads_as_week_off_not_weekend(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.hr.regularizations.approve', $this->dayRequest), [
                'resulting_status' => 'weekend',
            ]);

        $this->assertStringContainsString('Week-Off', session('success'));
    }

    #[Test]
    public function the_review_screen_offers_the_week_off_resolution(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.hr.regularizations.show', $this->dayRequest))
            ->assertOk()
            ->assertSee('value="weekend"', false);
    }

    #[Test]
    public function an_unknown_resolution_is_still_rejected(): void
    {
        // Widening the enum must not turn the field into a free-text column.
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.hr.regularizations.approve', $this->dayRequest), [
                'resulting_status' => 'nonsense',
            ])->assertSessionHasErrors('resulting_status');
    }
}
