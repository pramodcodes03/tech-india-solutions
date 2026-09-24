<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Attendance;
use App\Models\AttendanceRegularization;
use App\Models\Business;
use App\Models\BusinessWeekOff;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\AttendanceService;
use App\Services\Documents\DocumentDataResolver;
use App\Services\LeaveService;
use App\Support\Tenancy\CurrentBusiness;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUsers;

/**
 * The split attendance statuses: half a day worked alongside leave, and half a
 * day worked alongside a week-off — plus the monthly week-off allowance that
 * warns before an admin pushes someone over it.
 */
class AttendanceStatusTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    private Business $business;

    private Admin $admin;

    private Employee $employee;

    private LeaveType $casual;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();

        // End of the month, so every date under test is in the past.
        Carbon::setTestNow(Carbon::parse('2026-09-30 18:00:00'));

        $this->business = Business::create([
            'name' => 'Attn Co', 'slug' => 'attn-co',
            'currency_code' => 'INR', 'currency_symbol' => '₹',
        ]);
        app(CurrentBusiness::class)->set($this->business);

        $this->admin = Admin::create([
            'name' => 'Priya Admin', 'email' => 'priya@attn.test',
            'password' => bcrypt('password'), 'phone' => '9990001111',
            'status' => 'active', 'business_id' => $this->business->id,
        ]);
        $this->admin->assignRole('Admin');

        $this->employee = Employee::create([
            'business_id' => $this->business->id, 'employee_code' => 'EMP-001',
            'email' => 'asha@attn.test', 'first_name' => 'Asha', 'last_name' => 'Verma',
            'status' => 'active', 'password' => 'secret', 'joining_date' => '2024-01-01',
        ]);

        $this->casual = LeaveType::create([
            'business_id' => $this->business->id, 'name' => 'Casual Leave',
            'code' => 'CL', 'annual_quota' => 12, 'is_paid' => true,
        ]);

        // Sundays off, so week-off behaviour is deterministic.
        BusinessWeekOff::create([
            'business_id' => $this->business->id, 'day_of_week' => 0, 'is_off' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function attendance(string $date, array $attrs = []): Attendance
    {
        return Attendance::create(array_merge([
            'business_id' => $this->business->id,
            'employee_id' => $this->employee->id,
            'date' => $date,
            'check_in' => '09:00:00', 'check_out' => '13:00:00',
            'hours_worked' => 4, 'status' => 'half_day',
        ], $attrs));
    }

    private function approvedLeave(string $date, string $portion = 'second_half'): LeaveRequest
    {
        return LeaveRequest::create([
            'business_id' => $this->business->id, 'request_code' => 'LR-'.$date,
            'employee_id' => $this->employee->id, 'leave_type_id' => $this->casual->id,
            'from_date' => $date, 'to_date' => $date, 'days' => 0.5,
            'day_portion' => $portion, 'reason' => 'Half day off', 'status' => 'approved',
            'paid_days' => 0.5, 'unpaid_days' => 0,
        ]);
    }

    private function pendingHalfDayLeave(string $date, string $portion): LeaveRequest
    {
        return $this->pendingLeave($date, $date, $portion, 0.5);
    }

    private function pendingLeave(string $from, string $to, string $portion, float $days): LeaveRequest
    {
        LeaveBalance::updateOrCreate(
            [
                'business_id' => $this->business->id,
                'employee_id' => $this->employee->id,
                'leave_type_id' => $this->casual->id,
                'year' => 2026,
            ],
            ['allocated' => 12, 'used' => 0, 'pending' => 0, 'carried_forward' => 0],
        );

        return app(LeaveService::class)->submit([
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->casual->id,
            'from_date' => $from,
            'to_date' => $to,
            'day_portion' => $portion,
            'reason' => 'Half day off',
        ]);
    }

    private function approveLeave(LeaveRequest $request): LeaveRequest
    {
        return app(LeaveService::class)->approve($request, $this->admin->id);
    }

    private function summary(): array
    {
        return app(AttendanceService::class)->monthlySummary($this->employee->id, 9, 2026);
    }

    private function statuses(): array
    {
        return app(AttendanceService::class)->monthlyDayStatuses($this->employee->id, 9, 2026);
    }

    // ── 1. Half day duty + half day leave ────────────────────────────────

    #[Test]
    public function a_half_day_worked_with_approved_leave_reads_as_half_day_leave(): void
    {
        // Thu 10 Sep 2026 — a working day.
        $this->attendance('2026-09-10', ['half_day_portion' => 'first_half']);
        $this->approvedLeave('2026-09-10');

        $this->assertSame('half_day_leave', $this->statuses()['2026-09-10']);
    }

    #[Test]
    public function a_half_day_worked_without_leave_stays_a_plain_half_day(): void
    {
        $this->attendance('2026-09-10', ['half_day_portion' => 'first_half']);

        $this->assertSame('half_day', $this->statuses()['2026-09-10']);
    }

    #[Test]
    public function leave_on_the_first_of_the_month_still_resolves_the_first(): void
    {
        // Regression: expanding a leave that starts on the 1st used to advance
        // the month-start Carbon instance it was clamped against, so the 1st
        // fell out of the map entirely and the calendar read the raw DB status
        // ('half_day', amber) instead of 'half_day_leave'.
        $this->attendance('2026-09-01', ['half_day_portion' => 'first_half']);
        $this->approvedLeave('2026-09-01');

        $statuses = $this->statuses();

        $this->assertCount(30, $statuses, 'every day of September must be resolved');
        $this->assertSame('2026-09-01', array_key_first($statuses));
        $this->assertSame('half_day_leave', $statuses['2026-09-01']);
    }

    #[Test]
    public function a_plain_half_day_on_the_first_stays_amber(): void
    {
        // Same date, no leave applied and not a week-off: still a plain
        // half-day, exactly as before.
        $this->attendance('2026-09-01', ['half_day_portion' => 'first_half']);

        $this->assertSame('half_day', $this->statuses()['2026-09-01']);
    }

    #[Test]
    public function either_worked_half_paired_with_leave_reads_as_half_day_leave(): void
    {
        // Worked the first half, leave for the second.
        $this->attendance('2026-09-10', ['half_day_portion' => 'first_half']);
        $this->approvedLeave('2026-09-10', 'second_half');
        $this->assertSame('half_day_leave', $this->statuses()['2026-09-10']);

        // And the mirror case: leave for the first half, worked the second.
        $this->attendance('2026-09-11', ['half_day_portion' => 'second_half']);
        $this->approvedLeave('2026-09-11', 'first_half');
        $this->assertSame('half_day_leave', $this->statuses()['2026-09-11']);
    }

    #[Test]
    public function the_worked_half_is_recorded_and_labelled(): void
    {
        $record = $this->attendance('2026-09-10', ['half_day_portion' => 'first_half']);

        $this->assertSame('first_half', $record->fresh()->half_day_portion);
        $this->assertSame('Half Day / Leave', Attendance::statusLabel('half_day_leave'));
        $this->assertSame('HD/L', Attendance::statusCode('half_day_leave'));
    }

    // ── 1b. What leave APPROVAL writes onto the attendance row ───────────

    #[Test]
    public function approving_a_half_day_leave_keeps_the_worked_half_on_the_row(): void
    {
        // Worked the morning (punch data on the row), then the afternoon is
        // approved as leave. Approval used to stamp the whole day 'on_leave',
        // erasing the worked half — the manager read as absent all day.
        $this->attendance('2026-09-10', ['status' => 'present', 'check_out' => '14:00:00', 'hours_worked' => 4.95]);

        $this->approveLeave($this->pendingHalfDayLeave('2026-09-10', 'second_half'));

        $row = Attendance::where('employee_id', $this->employee->id)
            ->whereDate('date', '2026-09-10')->first();

        $this->assertSame('half_day_leave', $row->status);
        $this->assertSame('first_half', $row->half_day_portion, 'the half NOT covered by leave is the worked one');
        // The punch times that prove the worked half survive the approval.
        $this->assertSame('09:00:00', $row->check_in);
        $this->assertSame('14:00:00', $row->check_out);

        $this->assertSame('half_day_leave', $this->statuses()['2026-09-10']);
        $this->assertSame('Half Day / Leave', Attendance::statusLabel($row->status));
    }

    #[Test]
    public function approving_a_first_half_leave_marks_the_second_half_as_worked(): void
    {
        $this->attendance('2026-09-10', ['status' => 'present', 'check_in' => '14:00:00', 'check_out' => '18:30:00', 'hours_worked' => 4.5]);

        $this->approveLeave($this->pendingHalfDayLeave('2026-09-10', 'first_half'));

        $row = Attendance::where('employee_id', $this->employee->id)
            ->whereDate('date', '2026-09-10')->first();

        $this->assertSame('half_day_leave', $row->status);
        $this->assertSame('second_half', $row->half_day_portion);
    }

    #[Test]
    public function approving_a_full_day_leave_is_still_a_full_day_of_leave(): void
    {
        $this->approveLeave($this->pendingLeave('2026-09-10', '2026-09-10', 'full', 1.0));

        $row = Attendance::where('employee_id', $this->employee->id)
            ->whereDate('date', '2026-09-10')->first();

        $this->assertSame('on_leave', $row->status);
        $this->assertSame('on_leave', $this->statuses()['2026-09-10']);
    }

    #[Test]
    public function a_half_day_leave_with_no_work_recorded_stays_on_leave(): void
    {
        // Nothing was worked, so there is no worked half to preserve —
        // the row is written exactly as it was before this change.
        $this->approveLeave($this->pendingHalfDayLeave('2026-09-10', 'second_half'));

        $row = Attendance::where('employee_id', $this->employee->id)
            ->whereDate('date', '2026-09-10')->first();

        $this->assertSame('on_leave', $row->status);
        $this->assertNull($row->half_day_portion);
    }

    #[Test]
    public function a_biometric_resync_does_not_revert_an_approved_half_day_leave(): void
    {
        $this->attendance('2026-09-10', ['status' => 'present', 'check_out' => '14:00:00', 'hours_worked' => 4.95]);
        $this->approveLeave($this->pendingHalfDayLeave('2026-09-10', 'second_half'));

        // The nightly punch sync re-imports the same day.
        app(AttendanceService::class)->upsert([
            'business_id' => $this->business->id,
            'employee_id' => $this->employee->id,
            'date' => '2026-09-10',
            'check_in' => '09:00:00', 'check_out' => '14:00:00',
            'source' => 'biometric_api',
        ]);

        $row = Attendance::where('employee_id', $this->employee->id)
            ->whereDate('date', '2026-09-10')->first();

        $this->assertSame('half_day_leave', $row->status);
        $this->assertSame('first_half', $row->half_day_portion);
    }

    // ── 2. Half day duty + half day week off ─────────────────────────────

    #[Test]
    public function a_half_day_week_off_is_its_own_status(): void
    {
        $this->attendance('2026-09-10', [
            'status' => 'half_day_week_off', 'half_day_portion' => 'first_half',
        ]);

        $this->assertSame('half_day_week_off', $this->statuses()['2026-09-10']);
        $this->assertSame('Half Day / Week Off', Attendance::statusLabel('half_day_week_off'));
        $this->assertSame('HD/WO', Attendance::statusCode('half_day_week_off'));
    }

    #[Test]
    public function a_half_day_week_off_pays_half_present_plus_half_week_off(): void
    {
        $this->attendance('2026-09-10', [
            'status' => 'half_day_week_off', 'half_day_portion' => 'first_half',
        ]);

        $summary = app(AttendanceService::class)->monthlySummary($this->employee->id, 9, 2026);

        $this->assertSame(1, $summary['half_day_week_off']);
        // 0.5 worked + 0.5 week-off = one full paid day for that date.
        $this->assertEqualsWithDelta(0.5, $summary['week_offs'] - $this->septemberSundays(), 0.01);
    }

    /** Sundays in September 2026, each a full week-off. */
    private function septemberSundays(): float
    {
        $count = 0;
        for ($d = Carbon::create(2026, 9, 1); $d->month === 9; $d->addDay()) {
            if ($d->isSunday() && $d->lte(Carbon::today())) {
                $count++;
            }
        }

        return (float) $count;
    }

    // ── 3. Monthly week-off allowance ────────────────────────────────────

    #[Test]
    public function week_offs_count_a_full_as_one_and_a_half_as_a_half(): void
    {
        $service = app(AttendanceService::class);
        $base = $service->weekOffCountForMonth($this->employee->id, 9, 2026);

        $this->attendance('2026-09-10', [
            'status' => 'half_day_week_off', 'half_day_portion' => 'first_half',
        ]);
        $this->attendance('2026-09-11', ['status' => 'weekend']);

        $this->assertEqualsWithDelta(
            $base + 1.5,
            $service->weekOffCountForMonth($this->employee->id, 9, 2026),
            0.01,
        );
    }

    #[Test]
    public function a_projection_warns_only_once_the_allowance_is_passed(): void
    {
        $service = app(AttendanceService::class);

        // September 2026 has four Sundays before the 30th, so the employee is
        // already at the allowance of 4 without any correction.
        $current = $service->weekOffCountForMonth($this->employee->id, 9, 2026);
        $this->assertEqualsWithDelta(4.0, $current, 0.01);

        // A further half takes it to 4.5 → warn.
        $half = $service->projectWeekOff($this->employee->id, 9, 2026, 'half_day_week_off', '2026-09-10');
        $this->assertEqualsWithDelta(4.5, $half['projected'], 0.01);
        $this->assertTrue($half['exceeds']);

        // A full one takes it to 5 → warn.
        $full = $service->projectWeekOff($this->employee->id, 9, 2026, 'weekend', '2026-09-10');
        $this->assertEqualsWithDelta(5.0, $full['projected'], 0.01);
        $this->assertTrue($full['exceeds']);
    }

    #[Test]
    public function a_resolution_that_is_not_a_week_off_never_warns(): void
    {
        $projection = app(AttendanceService::class)
            ->projectWeekOff($this->employee->id, 9, 2026, 'present', '2026-09-10');

        $this->assertEqualsWithDelta(0.0, $projection['adding'], 0.01);
        $this->assertFalse($projection['exceeds']);
    }

    #[Test]
    public function correcting_a_day_that_is_already_a_week_off_replaces_rather_than_adds(): void
    {
        // 13 Sep 2026 is a Sunday and already counts as a week-off. Correcting
        // it to a half-day/week-off must not count it twice.
        $service = app(AttendanceService::class);

        $projection = $service->projectWeekOff($this->employee->id, 9, 2026, 'half_day_week_off', '2026-09-13');

        $this->assertEqualsWithDelta(3.0, $projection['current'], 0.01);
        $this->assertEqualsWithDelta(3.5, $projection['projected'], 0.01);
        $this->assertFalse($projection['exceeds']);
    }

    // ── The correction itself ────────────────────────────────────────────

    #[Test]
    public function hr_can_resolve_a_correction_as_half_day_week_off(): void
    {
        $request = AttendanceRegularization::create([
            'business_id' => $this->business->id, 'employee_id' => $this->employee->id,
            'date' => '2026-09-10', 'request_type' => 'missed_punch',
            'reason' => 'Worked the morning only', 'status' => 'pending',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.hr.regularizations.approve', $request), [
                'resulting_status' => 'half_day_week_off',
                'half_day_portion' => 'first_half',
            ])->assertRedirect();

        $this->assertDatabaseHas('attendance', [
            'employee_id' => $this->employee->id,
            'date' => '2026-09-10',
            'status' => 'half_day_week_off',
            'half_day_portion' => 'first_half',
        ]);
    }

    #[Test]
    public function a_full_day_resolution_clears_any_stale_half(): void
    {
        $this->attendance('2026-09-10', ['half_day_portion' => 'first_half']);

        $request = AttendanceRegularization::create([
            'business_id' => $this->business->id, 'employee_id' => $this->employee->id,
            'date' => '2026-09-10', 'request_type' => 'missed_punch',
            'reason' => 'Actually worked the full day', 'status' => 'pending',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.hr.regularizations.approve', $request), [
                'resulting_status' => 'present',
                'half_day_portion' => 'first_half',
            ])->assertRedirect();

        $this->assertDatabaseHas('attendance', [
            'employee_id' => $this->employee->id,
            'date' => '2026-09-10',
            'status' => 'present',
            'half_day_portion' => null,
        ]);
    }

    #[Test]
    public function the_review_screen_offers_the_new_resolution(): void
    {
        $request = AttendanceRegularization::create([
            'business_id' => $this->business->id, 'employee_id' => $this->employee->id,
            'date' => '2026-09-10', 'request_type' => 'missed_punch',
            'reason' => 'Half day', 'status' => 'pending',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.hr.regularizations.show', $request))
            ->assertOk()
            ->assertSee('value="half_day_week_off"', false)
            ->assertSee('name="half_day_portion"', false);
    }

    #[Test]
    public function the_employee_calendar_reports_the_months_week_off_count(): void
    {
        // September 2026 has four Sundays (6th, 13th, 20th, 27th) and the
        // business is Sundays-off, so those weigh 1.0 each. The 14th is a
        // Monday worked as half a day against a week-off, weighing 0.5.
        $this->attendance('2026-09-14', [
            'status' => 'half_day_week_off', 'half_day_portion' => 'first_half',
        ]);

        $response = $this->actingAs($this->employee, 'employee')
            ->get(route('employee.attendance.index', ['month' => 9, 'year' => 2026]))
            ->assertOk()
            ->assertSee('Week Off');

        $this->assertSame(
            4.5,
            $response->viewData('summary')['week_offs'],
        );
    }

    // ── 5. The printed register ──────────────────────────────────────────

    #[Test]
    public function the_register_reflects_a_correction_the_admin_applied(): void
    {
        $this->attendance('2026-09-10', [
            'status' => 'half_day_week_off', 'half_day_portion' => 'first_half',
        ]);

        $payload = app(DocumentDataResolver::class)
            ->resolve('muster_roll', null, ['month' => 9, 'year' => 2026]);

        // The register reads resolved statuses, so a corrected day shows up.
        $this->assertSame(
            'half_day_week_off',
            $payload['statuses'][$this->employee->id][10],
        );
    }

    #[Test]
    public function the_register_renders_with_every_status_present(): void
    {
        $this->attendance('2026-09-08', ['status' => 'present', 'check_out' => '18:00:00']);
        $this->attendance('2026-09-09', ['status' => 'absent']);
        $this->attendance('2026-09-10', ['half_day_portion' => 'first_half']);
        $this->approvedLeave('2026-09-11');
        $this->attendance('2026-09-11', ['half_day_portion' => 'first_half']);
        $this->attendance('2026-09-14', [
            'status' => 'half_day_week_off', 'half_day_portion' => 'second_half',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.documents.render', ['key' => 'muster_roll', 'month' => 9, 'year' => 2026]));

        $response->assertOk();
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    // ── 0.5 leave with no worked half ────────────────────────────────────

    #[Test]
    public function a_half_day_leave_with_no_hours_worked_is_half_leave_half_absent(): void
    {
        // The reported bug: approving a 0.5 leave stamps the row 'on_leave'
        // whatever the portion, so the calendar cell went solid blue and read
        // as a whole day of leave. Half of that day is not leave at all.
        $this->approvedLeave('2026-09-08', 'first_half');
        $this->attendance('2026-09-08', [
            'status' => 'on_leave', 'check_in' => null, 'check_out' => null, 'hours_worked' => 0,
        ]);

        $this->assertSame('half_day_leave_absent', $this->statuses()['2026-09-08']);
    }

    #[Test]
    public function a_half_day_leave_with_no_attendance_row_at_all_reads_the_same(): void
    {
        $this->approvedLeave('2026-09-08', 'second_half');

        $this->assertSame('half_day_leave_absent', $this->statuses()['2026-09-08']);
    }

    #[Test]
    public function an_absent_row_under_a_half_day_leave_reads_the_same(): void
    {
        $this->approvedLeave('2026-09-08', 'first_half');
        $this->attendance('2026-09-08', [
            'status' => 'absent', 'check_in' => null, 'check_out' => null, 'hours_worked' => 0,
        ]);

        $this->assertSame('half_day_leave_absent', $this->statuses()['2026-09-08']);
    }

    #[Test]
    public function a_half_day_leave_with_the_other_half_worked_is_still_half_day_leave(): void
    {
        // The new status must not swallow the case that already worked.
        $this->approvedLeave('2026-09-08', 'second_half');
        $this->attendance('2026-09-08', ['status' => 'on_leave', 'half_day_portion' => 'first_half']);

        $this->assertSame('half_day_leave', $this->statuses()['2026-09-08']);
    }

    #[Test]
    public function a_full_day_leave_is_untouched(): void
    {
        LeaveRequest::create([
            'business_id' => $this->business->id, 'request_code' => 'LR-FULL',
            'employee_id' => $this->employee->id, 'leave_type_id' => $this->casual->id,
            'from_date' => '2026-09-08', 'to_date' => '2026-09-08', 'days' => 1,
            'day_portion' => 'full', 'reason' => 'Whole day', 'status' => 'approved',
            'paid_days' => 1, 'unpaid_days' => 0,
        ]);

        $this->assertSame('on_leave', $this->statuses()['2026-09-08']);
    }

    #[Test]
    public function the_unworked_half_of_a_half_leave_day_is_loss_of_pay(): void
    {
        // Measured as a delta: the summary covers the whole month, and every
        // other weekday in it is an untouched absence.
        $before = $this->summary();
        $this->approvedLeave('2026-09-08', 'first_half');
        $after = $this->summary();

        $this->assertSame(1, $after['half_day_leave_absent']);
        $this->assertEqualsWithDelta(0.5, $after['paid_leave_days'], 0.01);
        // The day was a whole day of loss of pay; half of it is now sanctioned.
        $this->assertEqualsWithDelta(-0.5, $after['lop_days'] - $before['lop_days'], 0.01);
        $this->assertEqualsWithDelta(0.5, $after['paid_days'] - $before['paid_days'], 0.01);
    }

    // ── Manually marked leave is paid ────────────────────────────────────

    #[Test]
    public function leave_marked_by_hand_is_paid_not_deducted(): void
    {
        // No leave_request behind it: an admin set the status directly. That
        // used to fall through every paid bucket and land as loss of pay.
        $before = $this->summary();
        $this->attendance('2026-09-08', [
            'status' => 'on_leave', 'check_in' => null, 'check_out' => null,
            'hours_worked' => 0, 'source' => 'manual',
        ]);
        $after = $this->summary();

        // A whole day moves out of loss of pay and into paid.
        $this->assertEqualsWithDelta(1.0, $after['paid_days'] - $before['paid_days'], 0.01);
        $this->assertEqualsWithDelta(-1.0, $after['lop_days'] - $before['lop_days'], 0.01);
    }

    #[Test]
    public function leave_marked_by_hand_is_counted_as_a_leave_day_taken(): void
    {
        // Paying for the day but reporting zero days of leave is the half-fix:
        // the employee's "On Leave" tile and dashboard both read the leave-day
        // figure, and a hand-marked leave has no leave_request to be found in.
        $this->attendance('2026-09-08', [
            'status' => 'on_leave', 'check_in' => null, 'check_out' => null,
            'hours_worked' => 0, 'source' => 'manual',
        ]);

        $summary = $this->summary();

        $this->assertEqualsWithDelta(1.0, $summary['manual_leave_days'], 0.01);
        // What the tile prints: paid + unpaid leave days.
        $this->assertEqualsWithDelta(
            1.0,
            $summary['paid_leave_days'] + $summary['unpaid_leave_days'],
            0.01,
        );
    }

    #[Test]
    public function a_hand_marked_leave_day_is_not_also_counted_as_a_request(): void
    {
        // Folding manual leave into paid_leave_days must not double-count a day
        // that already has an approved request behind it.
        $this->approvedLeave('2026-09-08', 'first_half');
        $this->attendance('2026-09-08', [
            'status' => 'on_leave', 'check_in' => null, 'check_out' => null, 'hours_worked' => 0,
        ]);

        $summary = $this->summary();

        $this->assertEqualsWithDelta(0.0, $summary['manual_leave_days'], 0.01);
        $this->assertEqualsWithDelta(0.5, $summary['paid_leave_days'], 0.01);
    }

    #[Test]
    public function request_backed_leave_is_not_counted_twice(): void
    {
        // The manual-leave rule must not double-pay a day that a leave request
        // already accounts for.
        $before = $this->summary();
        $this->approvedLeave('2026-09-08', 'first_half');
        $this->approvedLeave('2026-09-09', 'first_half');
        $after = $this->summary();

        // Two half-days of approved paid leave: 1.0 in total. If the manual
        // rule also claimed them this would read 2.0 or 3.0.
        $this->assertEqualsWithDelta(1.0, $after['paid_leave_days'], 0.01);
        $this->assertEqualsWithDelta(1.0, $after['paid_days'] - $before['paid_days'], 0.01);
    }

    #[Test]
    public function an_admin_can_record_the_new_leave_week_off_status(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.hr.attendance.store'), [
                'employee_id' => $this->employee->id,
                'date' => '2026-09-08',
                'status' => 'leave_week_off',
            ])->assertRedirect();

        $this->assertSame('leave_week_off', Attendance::firstOrFail()->status);
        $this->assertSame('leave_week_off', $this->statuses()['2026-09-08']);
    }

    #[Test]
    public function a_leave_week_off_day_is_fully_paid(): void
    {
        // Half rostered off, half sanctioned leave — the employee was never
        // due in, so none of it is a deduction.
        $before = $this->summary();
        $this->attendance('2026-09-08', [
            'status' => 'leave_week_off', 'check_in' => null, 'check_out' => null, 'hours_worked' => 0,
        ]);
        $after = $this->summary();

        $this->assertSame(1, $after['leave_week_off']);
        $this->assertEqualsWithDelta(1.0, $after['paid_days'] - $before['paid_days'], 0.01);
        $this->assertEqualsWithDelta(-1.0, $after['lop_days'] - $before['lop_days'], 0.01);
    }
}
