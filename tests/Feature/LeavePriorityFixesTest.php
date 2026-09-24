<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Business;
use App\Models\BusinessWeekOff;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\AttendanceService;
use App\Services\LeaveRequestReportService;
use App\Services\LeaveService;
use App\Support\HrSettings;
use App\Support\Tenancy\CurrentBusiness;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUsers;

/**
 * The leave priorities from the client's "Priority changes" brief:
 * half-day application, the combination-leave toggle, and the paid/unpaid
 * balance rules.
 */
class LeavePriorityFixesTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    private Business $business;

    private Employee $employee;

    private LeaveType $casual;

    private LeaveType $lwp;

    private ?LeaveType $sick = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();

        // A Monday, so the single test date is a working day.
        Carbon::setTestNow(Carbon::parse('2026-09-07 10:00:00'));

        $this->business = Business::create([
            'name' => 'Leave Co', 'slug' => 'leave-co',
            'currency_code' => 'INR', 'currency_symbol' => '₹',
        ]);
        app(CurrentBusiness::class)->set($this->business);

        $department = Department::create([
            'business_id' => $this->business->id, 'name' => 'Ops', 'code' => 'OPS',
        ]);

        $this->employee = Employee::create([
            'business_id' => $this->business->id, 'employee_code' => 'EMP-001',
            'email' => 'asha@leave.test', 'first_name' => 'Asha', 'last_name' => 'Verma',
            'department_id' => $department->id, 'status' => 'active',
            'password' => 'secret', 'joining_date' => '2024-01-01',
            'confirmation_date' => '2024-07-01',
        ]);

        $this->casual = LeaveType::create([
            'business_id' => $this->business->id, 'name' => 'Casual Leave',
            'code' => 'CL', 'annual_quota' => 12, 'is_paid' => true,
        ]);

        $this->lwp = LeaveType::create([
            'business_id' => $this->business->id, 'name' => 'Leave Without Pay',
            'code' => 'LWP', 'annual_quota' => 0, 'is_paid' => false,
        ]);

        LeaveBalance::create([
            'business_id' => $this->business->id, 'employee_id' => $this->employee->id,
            'leave_type_id' => $this->casual->id, 'year' => 2026,
            'allocated' => 12, 'used' => 0,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * A second paid leave type, created on demand.
     *
     * Deliberately Sick rather than Earned: EL carries its own working-days
     * eligibility gate, and these tests are about whether two halves of a date
     * may come from two types at all — which holds for any pair.
     */
    private function sick(): LeaveType
    {
        return $this->sick ??= tap(LeaveType::create([
            'business_id' => $this->business->id, 'name' => 'Sick Leave',
            'code' => 'SL', 'annual_quota' => 6, 'is_paid' => true,
        ]), fn (LeaveType $type) => LeaveBalance::create([
            'business_id' => $this->business->id, 'employee_id' => $this->employee->id,
            'leave_type_id' => $type->id, 'year' => 2026,
            'allocated' => 6, 'used' => 0,
        ]));
    }

    private function apply(array $overrides = [])
    {
        return $this->actingAs($this->employee, 'employee')
            ->post(route('employee.leaves.store'), array_merge([
                'leave_type_id' => $this->casual->id,
                'from_date' => '2026-09-08',
                'to_date' => '2026-09-08',
                'day_portion' => 'full',
                'reason' => 'Personal work at home',
            ], $overrides));
    }

    // ── Priority 1 · Half-day leave ──────────────────────────────────────

    #[Test]
    public function an_employee_can_apply_for_a_first_half_day(): void
    {
        $this->apply(['day_portion' => 'first_half'])->assertRedirect();

        $request = LeaveRequest::where('employee_id', $this->employee->id)->firstOrFail();
        $this->assertSame('first_half', $request->day_portion);
        $this->assertEquals(0.5, (float) $request->days);
    }

    #[Test]
    public function an_employee_can_apply_for_a_second_half_day(): void
    {
        $this->apply(['day_portion' => 'second_half'])->assertRedirect();

        $request = LeaveRequest::where('employee_id', $this->employee->id)->firstOrFail();
        $this->assertSame('second_half', $request->day_portion);
        $this->assertEquals(0.5, (float) $request->days);
    }

    #[Test]
    public function a_multi_day_range_is_always_full_days(): void
    {
        // Half-day only means anything on a single date; a range silently
        // normalises rather than half-counting the first day.
        $this->apply([
            'from_date' => '2026-09-08', 'to_date' => '2026-09-10',
            'day_portion' => 'first_half',
        ])->assertRedirect();

        $request = LeaveRequest::where('employee_id', $this->employee->id)->firstOrFail();
        $this->assertSame('full', $request->day_portion);
        $this->assertEquals(3.0, (float) $request->days);
    }

    #[Test]
    public function the_apply_form_offers_both_half_day_options(): void
    {
        $this->actingAs($this->employee, 'employee')
            ->get(route('employee.leaves.create'))
            ->assertOk()
            ->assertSee('value="first_half"', false)
            ->assertSee('value="second_half"', false);
    }

    // ── Priority 1 · Combination Leave ───────────────────────────────────

    private function applyCombined(array $overrides = [])
    {
        return $this->actingAs($this->employee, 'employee')
            ->post(route('employee.leaves.store'), array_merge([
                'from_date' => '2026-09-08',
                'to_date' => '2026-09-08',
                'day_portion' => 'full',
                'reason' => 'Half casual, half unpaid',
                'splits' => [
                    ['leave_type_id' => $this->casual->id, 'days' => 0.5],
                    ['leave_type_id' => $this->lwp->id, 'days' => 0.5],
                ],
            ], $overrides));
    }

    #[Test]
    public function combination_leave_works_on_a_full_day_when_enabled(): void
    {
        $this->applyCombined()->assertRedirect();

        $request = LeaveRequest::where('employee_id', $this->employee->id)->firstOrFail();
        $this->assertTrue((bool) $request->is_combined);
        $this->assertCount(2, $request->splits);
    }

    #[Test]
    public function combination_leave_is_refused_when_the_business_turns_it_off(): void
    {
        HrSettings::setForBusiness('leave_combination_enabled', $this->business->id, 0, 'leave');

        $this->applyCombined()->assertSessionHas('error');

        $this->assertDatabaseCount('leave_requests', 0);
    }

    #[Test]
    public function combination_leave_is_refused_on_a_half_day(): void
    {
        // Half a day cannot be split further, so the server refuses it even if
        // the form is bypassed.
        $this->applyCombined([
            'day_portion' => 'first_half',
            'splits' => [
                ['leave_type_id' => $this->casual->id, 'days' => 0.25],
                ['leave_type_id' => $this->lwp->id, 'days' => 0.25],
            ],
        ])->assertSessionHas('error');

        $this->assertDatabaseCount('leave_requests', 0);
    }

    #[Test]
    public function the_apply_form_hides_the_combine_option_when_it_is_switched_off(): void
    {
        HrSettings::setForBusiness('leave_combination_enabled', $this->business->id, 0, 'leave');

        $this->actingAs($this->employee, 'employee')
            ->get(route('employee.leaves.create'))
            ->assertOk()
            ->assertDontSee('Combine two or more leave types');
    }

    #[Test]
    public function the_apply_form_shows_the_combine_option_by_default(): void
    {
        $this->actingAs($this->employee, 'employee')
            ->get(route('employee.leaves.create'))
            ->assertOk()
            ->assertSee('Combine two or more leave types');
    }

    // ── Priority 2 · Paid vs unpaid on an empty balance ──────────────────

    #[Test]
    public function paid_leave_is_refused_once_the_balance_is_gone(): void
    {
        LeaveBalance::where('employee_id', $this->employee->id)->update(['allocated' => 0, 'used' => 0]);

        $this->apply()->assertSessionHas('error');

        $this->assertDatabaseCount('leave_requests', 0);
    }

    #[Test]
    public function unpaid_leave_stays_open_when_the_lwp_exception_is_on(): void
    {
        LeaveBalance::where('employee_id', $this->employee->id)->update(['allocated' => 0, 'used' => 0]);

        $this->apply(['leave_type_id' => $this->lwp->id])->assertRedirect();

        $this->assertDatabaseCount('leave_requests', 1);
    }

    #[Test]
    public function unpaid_leave_is_refused_too_when_the_exception_is_switched_off(): void
    {
        LeaveBalance::where('employee_id', $this->employee->id)->update(['allocated' => 0, 'used' => 0]);
        HrSettings::setForBusiness('leave_lwp_exception_enabled', $this->business->id, 0, 'leave');

        $this->apply(['leave_type_id' => $this->lwp->id])->assertSessionHas('error');

        $this->assertDatabaseCount('leave_requests', 0);
    }

    #[Test]
    public function the_whole_gate_can_be_switched_off(): void
    {
        // Some businesses let people go negative and settle it at approval.
        LeaveBalance::where('employee_id', $this->employee->id)->update(['allocated' => 0, 'used' => 0]);
        HrSettings::setForBusiness('leave_balance_gate_enabled', $this->business->id, 0, 'leave');

        $this->apply()->assertRedirect();

        $this->assertDatabaseCount('leave_requests', 1);
    }

    // ── Priority 3 · Leave export & report ───────────────────────────────

    private function seedForExport(): Admin
    {
        $admin = Admin::create([
            'name' => 'Priya HR', 'email' => 'priya@leave.test',
            'password' => bcrypt('password'), 'phone' => '9990001111',
            'status' => 'active', 'business_id' => $this->business->id,
        ]);
        $admin->assignRole('Admin');

        $this->apply(['from_date' => '2026-09-08', 'to_date' => '2026-09-08']);
        $this->apply([
            'from_date' => '2026-10-05', 'to_date' => '2026-10-07',
            'reason' => 'Family function out of town',
        ]);

        return $admin;
    }

    #[Test]
    public function the_report_lists_employee_id_and_name_in_separate_columns(): void
    {
        $this->seedForExport();

        $rows = app(LeaveRequestReportService::class)->rows([]);

        $this->assertCount(2, $rows);
        // Heading order: code, id, name, department, …
        $this->assertSame('EMP-001', $rows[0][1]);
        $this->assertSame('Asha Verma', $rows[0][2]);
        $this->assertSame('Ops', $rows[0][3]);
    }

    #[Test]
    public function the_report_names_whoever_approved_the_leave(): void
    {
        $admin = $this->seedForExport();
        $request = LeaveRequest::where('employee_id', $this->employee->id)->latest('id')->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.hr.leaves.approve', $request), ['remarks' => 'Fine']);

        $approverColumn = 10;
        $rows = app(LeaveRequestReportService::class)->rows(['status' => 'approved']);

        $this->assertCount(1, $rows);
        $this->assertSame('Priya HR', $rows[0][$approverColumn]);
    }

    #[Test]
    public function the_month_and_year_filters_narrow_the_report(): void
    {
        $this->seedForExport();

        $september = app(LeaveRequestReportService::class)->rows(['month' => 9, 'year' => 2026]);
        $october = app(LeaveRequestReportService::class)->rows(['month' => 10, 'year' => 2026]);

        $this->assertCount(1, $september);
        $this->assertCount(1, $october);
        $this->assertSame('08-Sep-2026', $september[0][5]);
    }

    #[Test]
    public function a_date_filter_matches_leave_that_spans_that_day(): void
    {
        $this->seedForExport();

        // 6 Oct is the middle of the 5–7 Oct request, not its start date.
        $rows = app(LeaveRequestReportService::class)->rows(['date' => '2026-10-06']);

        $this->assertCount(1, $rows);
        $this->assertSame('05-Oct-2026', $rows[0][5]);
    }

    #[Test]
    public function the_department_filter_narrows_the_report(): void
    {
        $this->seedForExport();

        $this->assertCount(2, app(LeaveRequestReportService::class)->rows([
            'department_id' => $this->employee->department_id,
        ]));
        $this->assertCount(0, app(LeaveRequestReportService::class)->rows([
            'department_id' => 999999,
        ]));
    }

    #[Test]
    public function an_empty_filter_value_is_ignored_rather_than_matched(): void
    {
        $this->seedForExport();

        // The select options post '' for "All"; that must not filter on ''.
        $rows = app(LeaveRequestReportService::class)->rows([
            'status' => '', 'department_id' => '', 'month' => '', 'year' => '', 'date' => '',
        ]);

        $this->assertCount(2, $rows);
    }

    #[Test]
    public function the_excel_export_downloads_a_spreadsheet(): void
    {
        $admin = $this->seedForExport();

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.hr.leaves.export.excel', ['month' => 9, 'year' => 2026]));

        $response->assertOk();
        $this->assertStringContainsString('.xlsx', $response->headers->get('content-disposition'));
    }

    #[Test]
    public function the_pdf_export_downloads_a_pdf(): void
    {
        $admin = $this->seedForExport();

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.hr.leaves.export.pdf', ['month' => 9, 'year' => 2026]));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    #[Test]
    public function a_combined_request_reports_every_type_it_was_funded_from(): void
    {
        $this->applyCombined();

        $rows = app(LeaveRequestReportService::class)->rows([]);

        // Naming only the primary type would misreport a combined request.
        $this->assertStringContainsString('CL', $rows[0][4]);
        $this->assertStringContainsString('LWP', $rows[0][4]);
    }

    // ── The reported half-day failure ────────────────────────────────────

    #[Test]
    public function empty_combine_rows_do_not_block_an_ordinary_application(): void
    {
        // The combine rows are hidden with x-show, which is display:none — the
        // inputs stay in the DOM and still post. Every application therefore
        // arrived carrying two blank split rows, and required_with:splits
        // rejected it. This is the "cannot apply for half-day" report.
        $this->apply([
            'day_portion' => 'second_half',
            'splits' => [
                ['leave_type_id' => '', 'days' => ''],
                ['leave_type_id' => '', 'days' => ''],
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $request = LeaveRequest::where('employee_id', $this->employee->id)->firstOrFail();
        $this->assertSame('second_half', $request->day_portion);
        $this->assertEquals(0.5, (float) $request->days);
        $this->assertFalse((bool) $request->is_combined);
    }

    #[Test]
    public function a_partly_filled_combine_row_is_ignored_rather_than_rejected(): void
    {
        $this->apply([
            'splits' => [
                ['leave_type_id' => (string) $this->casual->id, 'days' => ''],
                ['leave_type_id' => '', 'days' => ''],
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseCount('leave_requests', 1);
    }

    // ── Duplicate leave for the same date ────────────────────────────────

    #[Test]
    public function a_second_request_for_the_same_date_is_refused(): void
    {
        $this->apply()->assertRedirect();

        $this->apply(['reason' => 'Applying for the same day again'])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('leave_requests', 1);
    }

    #[Test]
    public function the_refusal_uses_the_wording_the_client_asked_for(): void
    {
        $this->apply();
        $this->apply(['reason' => 'Same day again please']);

        $this->assertStringContainsString('You have already applied for leave for this date.', session('error'));
    }

    #[Test]
    public function the_two_halves_of_one_day_can_be_funded_from_different_types(): void
    {
        // One working day is two halves, and an employee may hold only half a
        // day of each type — first half Casual, second half Earned is a real
        // request, not a duplicate.
        $this->apply(['day_portion' => 'first_half'])->assertRedirect();

        $this->apply([
            'day_portion' => 'second_half',
            'leave_type_id' => $this->sick()->id,
            'reason' => 'Second half against earned leave',
        ])->assertRedirect();

        $this->assertDatabaseCount('leave_requests', 2);
        $this->assertEqualsWithDelta(
            1.0,
            (float) LeaveRequest::where('employee_id', $this->employee->id)->sum('days'),
            0.01,
            'the two halves add up to exactly one day',
        );
    }

    #[Test]
    public function the_same_half_of_a_day_cannot_be_claimed_twice(): void
    {
        $this->apply(['day_portion' => 'second_half'])->assertRedirect();

        $this->apply([
            'day_portion' => 'second_half',
            'leave_type_id' => $this->sick()->id,
            'reason' => 'Trying the same half again',
        ])->assertSessionHas('error');

        $this->assertStringContainsString('second half of this date', session('error'));
        $this->assertDatabaseCount('leave_requests', 1);
    }

    #[Test]
    public function a_half_day_still_blocks_a_full_day_on_the_same_date(): void
    {
        $this->apply(['day_portion' => 'first_half'])->assertRedirect();

        $this->apply([
            'day_portion' => 'full',
            'leave_type_id' => $this->sick()->id,
            'reason' => 'A whole day over an existing half',
        ])->assertSessionHas('error');

        $this->assertDatabaseCount('leave_requests', 1);
    }

    #[Test]
    public function a_half_day_does_not_unblock_a_multi_day_range_covering_it(): void
    {
        // The half-day exemption is for one date only; a range spanning that
        // date is full days throughout and must still collide.
        $this->apply(['day_portion' => 'first_half'])->assertRedirect();

        $this->apply([
            'from_date' => '2026-09-07', 'to_date' => '2026-09-09',
            'day_portion' => 'second_half',
            'leave_type_id' => $this->sick()->id,
            'reason' => 'A range that swallows the half day',
        ])->assertSessionHas('error');

        $this->assertDatabaseCount('leave_requests', 1);
    }

    #[Test]
    public function both_halves_of_a_day_approved_read_as_a_whole_day_off(): void
    {
        // Attendance must not conclude the employee worked the other half.
        $this->apply(['day_portion' => 'first_half']);
        $this->apply([
            'day_portion' => 'second_half',
            'leave_type_id' => $this->sick()->id,
            'reason' => 'Second half against earned leave',
        ]);
        LeaveRequest::query()->update(['status' => 'approved']);

        $this->assertSame(
            'full',
            app(AttendanceService::class)->approvedLeavePortion($this->employee->id, '2026-09-08'),
        );
    }

    #[Test]
    public function an_overlapping_range_is_refused_not_just_an_exact_match(): void
    {
        $this->apply(['from_date' => '2026-09-08', 'to_date' => '2026-09-10']);

        // 09 Sep sits inside the existing 08–10 request.
        $this->apply([
            'from_date' => '2026-09-09', 'to_date' => '2026-09-11',
            'reason' => 'Overlaps the middle of the first one',
        ])->assertSessionHas('error');

        $this->assertDatabaseCount('leave_requests', 1);
    }

    #[Test]
    public function a_cancelled_request_releases_its_dates(): void
    {
        $this->apply();
        LeaveRequest::query()->update(['status' => 'cancelled']);

        $this->apply(['reason' => 'Re-applying after cancelling'])->assertRedirect();

        $this->assertDatabaseCount('leave_requests', 2);
    }

    #[Test]
    public function a_rejected_request_also_releases_its_dates(): void
    {
        $this->apply();
        LeaveRequest::query()->update(['status' => 'rejected']);

        $this->apply(['reason' => 'Re-applying after rejection'])->assertRedirect();

        $this->assertDatabaseCount('leave_requests', 2);
    }

    #[Test]
    public function a_different_date_is_still_allowed(): void
    {
        $this->apply(['from_date' => '2026-09-08', 'to_date' => '2026-09-08']);

        $this->apply([
            'from_date' => '2026-09-09', 'to_date' => '2026-09-09',
            'reason' => 'A genuinely different day',
        ])->assertRedirect();

        $this->assertDatabaseCount('leave_requests', 2);
    }

    #[Test]
    public function two_pending_duplicates_cannot_both_be_approved(): void
    {
        // Pairs raised before duplicate-prevention existed can still be sitting
        // in the queue; approving both would deduct the same days twice.
        $admin = Admin::create([
            'name' => 'Priya HR', 'email' => 'priya2@leave.test',
            'password' => bcrypt('password'), 'phone' => '9990003333',
            'status' => 'active', 'business_id' => $this->business->id,
        ]);
        $admin->assignRole('Admin');

        $first = LeaveRequest::create([
            'business_id' => $this->business->id, 'request_code' => 'LR-DUP-1',
            'employee_id' => $this->employee->id, 'leave_type_id' => $this->casual->id,
            'from_date' => '2026-09-08', 'to_date' => '2026-09-08', 'days' => 1,
            'day_portion' => 'full', 'reason' => 'First', 'status' => 'pending',
        ]);
        $second = LeaveRequest::create([
            'business_id' => $this->business->id, 'request_code' => 'LR-DUP-2',
            'employee_id' => $this->employee->id, 'leave_type_id' => $this->casual->id,
            'from_date' => '2026-09-08', 'to_date' => '2026-09-08', 'days' => 1,
            'day_portion' => 'full', 'reason' => 'Second', 'status' => 'pending',
        ]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.hr.leaves.approve', $first), [])
            ->assertRedirect();
        $this->assertSame('approved', $first->fresh()->status);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.hr.leaves.approve', $second), [])
            ->assertSessionHas('error');
        $this->assertSame('pending', $second->fresh()->status);
    }

    // ── Day count breakdown ──────────────────────────────────────────────

    #[Test]
    public function the_breakdown_explains_a_range_shorter_than_its_span(): void
    {
        BusinessWeekOff::create([
            'business_id' => $this->business->id, 'day_of_week' => 0, 'is_off' => true,
        ]);

        // 12 Sep 2026 is a Saturday, 13th a Sunday, 14th a Monday.
        $this->apply(['from_date' => '2026-09-12', 'to_date' => '2026-09-14']);

        $request = LeaveRequest::where('employee_id', $this->employee->id)->firstOrFail();
        $this->assertEquals(2.0, (float) $request->days);

        $breakdown = app(LeaveService::class)->dayBreakdown($request->load('employee'));

        $this->assertCount(3, $breakdown['dates']);
        $this->assertSame('Week-off', $breakdown['dates'][1]['reason']);
        $this->assertFalse($breakdown['dates'][1]['counted']);
        $this->assertFalse($breakdown['drifted']);
    }

    #[Test]
    public function the_breakdown_flags_a_calendar_changed_after_the_fact(): void
    {
        // The client's exact case: LR-202608-0018 was filed as 3 days on
        // 8 Aug under a Sunday week-off, and the Sunday row was unticked three
        // weeks later. The stored figure is a snapshot; recomputing the same
        // dates today gives a different answer, and the reviewer needs telling.
        // Mon 14th to Wed 16th — no weekend in the range, so the result does
        // not depend on any week-off configuration.
        $this->apply(['from_date' => '2026-09-14', 'to_date' => '2026-09-16']);

        $request = LeaveRequest::where('employee_id', $this->employee->id)->firstOrFail();
        $this->assertEquals(3.0, (float) $request->days);

        // Stand in for the historical snapshot taken under the old calendar.
        $request->update(['days' => 2.0]);

        $breakdown = app(LeaveService::class)
            ->dayBreakdown($request->fresh()->load('employee'));

        $this->assertTrue($breakdown['drifted']);
        $this->assertEquals(2.0, $breakdown['stored']);
        $this->assertEquals(3.0, $breakdown['recomputed']);
    }

    // ── Manager approvals: approve or reject, nothing else ───────────────

    /** A manager for our employee, plus a pending request to act on. */
    private function manager(): Employee
    {
        $manager = Employee::create([
            'business_id' => $this->business->id, 'employee_code' => 'MGR-001',
            'email' => 'mgr@leave.test', 'first_name' => 'Manav', 'last_name' => 'Rao',
            'department_id' => $this->employee->department_id, 'status' => 'active',
            'password' => 'secret', 'joining_date' => '2024-01-01',
        ]);

        $this->employee->update(['reporting_manager_id' => $manager->id]);

        return $manager;
    }

    #[Test]
    public function the_manager_screen_no_longer_offers_a_paid_days_box(): void
    {
        $manager = $this->manager();
        $this->apply();

        $this->actingAs($manager, 'employee')
            ->get(route('employee.team-leaves.index'))
            ->assertOk()
            ->assertDontSee('name="paid_days"', false);
    }

    #[Test]
    public function a_manager_approval_pays_what_the_balance_can_fund(): void
    {
        $manager = $this->manager();
        $this->apply();
        $request = LeaveRequest::where('employee_id', $this->employee->id)->firstOrFail();

        $this->actingAs($manager, 'employee')
            ->post(route('employee.team-leaves.approve', $request), ['remarks' => 'Fine'])
            ->assertRedirect();

        $request->refresh();
        $this->assertSame('approved', $request->status);
        // 12 days allocated, 1 day requested — the whole day is paid.
        $this->assertEquals(1.0, (float) $request->paid_days);
        $this->assertEquals(0.0, (float) $request->unpaid_days);
    }

    #[Test]
    public function a_posted_paid_days_value_is_ignored_on_the_manager_path(): void
    {
        // The control is gone from the screen; it must also be impossible to
        // force through a crafted request.
        $manager = $this->manager();
        $this->apply();
        $request = LeaveRequest::where('employee_id', $this->employee->id)->firstOrFail();

        $this->actingAs($manager, 'employee')
            ->post(route('employee.team-leaves.approve', $request), [
                'remarks' => 'Trying to split it',
                'paid_days' => 0,
            ])->assertRedirect();

        $request->refresh();
        $this->assertEquals(1.0, (float) $request->paid_days, 'paid_days from the request must be ignored');
        $this->assertEquals(0.0, (float) $request->unpaid_days);
    }

    #[Test]
    public function a_manager_approval_falls_back_to_lop_when_the_balance_is_short(): void
    {
        $manager = $this->manager();
        LeaveBalance::where('employee_id', $this->employee->id)
            ->update(['allocated' => 1, 'used' => 0]);

        // With the balance gate on, over-applying is refused at submission —
        // this is the other configuration, where a business lets the request
        // through and settles the paid/unpaid split at approval.
        HrSettings::setForBusiness('leave_balance_gate_enabled', $this->business->id, 0, 'leave');

        // Two days requested against one day of balance.
        $this->apply(['from_date' => '2026-09-14', 'to_date' => '2026-09-15']);
        $request = LeaveRequest::where('employee_id', $this->employee->id)->firstOrFail();

        $this->actingAs($manager, 'employee')
            ->post(route('employee.team-leaves.approve', $request), [])
            ->assertRedirect();

        $request->refresh();
        $this->assertEquals(1.0, (float) $request->paid_days);
        $this->assertEquals(1.0, (float) $request->unpaid_days);
    }

    #[Test]
    public function a_manager_can_still_reject(): void
    {
        $manager = $this->manager();
        $this->apply();
        $request = LeaveRequest::where('employee_id', $this->employee->id)->firstOrFail();

        $this->actingAs($manager, 'employee')
            ->post(route('employee.team-leaves.reject', $request), ['remarks' => 'Not this week'])
            ->assertRedirect();

        $this->assertSame('rejected', $request->fresh()->status);
    }

    #[Test]
    public function hr_keeps_the_paid_unpaid_split(): void
    {
        // Removing it from the manager must not remove it from payroll.
        $admin = Admin::create([
            'name' => 'Priya HR', 'email' => 'priya3@leave.test',
            'password' => bcrypt('password'), 'phone' => '9990004444',
            'status' => 'active', 'business_id' => $this->business->id,
        ]);
        $admin->assignRole('Admin');

        $this->apply(['from_date' => '2026-09-14', 'to_date' => '2026-09-15']);
        $request = LeaveRequest::where('employee_id', $this->employee->id)->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.hr.leaves.approve', $request), ['paid_days' => 1])
            ->assertRedirect();

        $request->refresh();
        $this->assertEquals(1.0, (float) $request->paid_days);
        $this->assertEquals(1.0, (float) $request->unpaid_days);
    }

    // ── Cancelling leave ─────────────────────────────────────────────────

    /** Total days currently consumed from the casual bucket. */
    private function casualUsed(): float
    {
        return (float) LeaveBalance::where('employee_id', $this->employee->id)
            ->where('leave_type_id', $this->casual->id)
            ->value('used');
    }

    #[Test]
    public function an_employee_can_cancel_a_pending_request(): void
    {
        $this->apply();
        $request = LeaveRequest::where('employee_id', $this->employee->id)->firstOrFail();

        $this->actingAs($this->employee, 'employee')
            ->post(route('employee.leaves.cancel', $request))
            ->assertRedirect();

        $this->assertSame('cancelled', $request->fresh()->status);
    }

    #[Test]
    public function an_employee_cannot_cancel_an_approved_request(): void
    {
        $admin = Admin::create([
            'name' => 'Priya HR', 'email' => 'priya4@leave.test',
            'password' => bcrypt('password'), 'phone' => '9990005555',
            'status' => 'active', 'business_id' => $this->business->id,
        ]);
        $admin->assignRole('Admin');

        $this->apply();
        $request = LeaveRequest::where('employee_id', $this->employee->id)->firstOrFail();
        $this->actingAs($admin, 'admin')->post(route('admin.hr.leaves.approve', $request), []);

        $this->assertSame('approved', $request->fresh()->status);
        $usedAfterApproval = $this->casualUsed();

        $this->actingAs($this->employee, 'employee')
            ->post(route('employee.leaves.cancel', $request))
            ->assertSessionHas('error');

        // Still approved, and — the whole point — the days are still consumed.
        $this->assertSame('approved', $request->fresh()->status);
        $this->assertEquals($usedAfterApproval, $this->casualUsed());
    }

    #[Test]
    public function cancelling_a_pending_request_only_releases_the_hold(): void
    {
        $before = $this->casualUsed();

        $this->apply();
        $request = LeaveRequest::where('employee_id', $this->employee->id)->firstOrFail();

        $this->actingAs($this->employee, 'employee')
            ->post(route('employee.leaves.cancel', $request));

        // A pending request never debited `used`, so cancelling must not credit
        // anything back to it either.
        $this->assertEquals($before, $this->casualUsed());
    }

    #[Test]
    public function an_approved_leave_from_a_past_month_cannot_be_reclaimed(): void
    {
        // The reported abuse: cancelling an old approved leave to get the days
        // back into the current balance.
        $admin = Admin::create([
            'name' => 'Priya HR', 'email' => 'priya5@leave.test',
            'password' => bcrypt('password'), 'phone' => '9990006666',
            'status' => 'active', 'business_id' => $this->business->id,
        ]);
        $admin->assignRole('Admin');

        $past = LeaveRequest::create([
            'business_id' => $this->business->id, 'request_code' => 'LR-PAST-1',
            'employee_id' => $this->employee->id, 'leave_type_id' => $this->casual->id,
            'from_date' => '2026-06-10', 'to_date' => '2026-06-10', 'days' => 1,
            'day_portion' => 'full', 'reason' => 'Old leave', 'status' => 'approved',
            'paid_days' => 1, 'unpaid_days' => 0,
        ]);

        LeaveBalance::where('employee_id', $this->employee->id)
            ->where('leave_type_id', $this->casual->id)
            ->update(['used' => 1]);

        $this->actingAs($this->employee, 'employee')
            ->post(route('employee.leaves.cancel', $past))
            ->assertSessionHas('error');

        $this->assertSame('approved', $past->fresh()->status);
        $this->assertEquals(1.0, $this->casualUsed());
    }

    #[Test]
    public function the_cancel_button_is_only_offered_on_pending_requests(): void
    {
        $this->apply();
        $request = LeaveRequest::where('employee_id', $this->employee->id)->firstOrFail();

        $this->actingAs($this->employee, 'employee')
            ->get(route('employee.leaves.show', $request))
            ->assertOk()
            ->assertSee('Cancel Request');

        $request->update(['status' => 'approved']);

        $this->actingAs($this->employee, 'employee')
            ->get(route('employee.leaves.show', $request))
            ->assertOk()
            ->assertDontSee('Cancel Request')
            ->assertSee('can no longer be cancelled here');
    }

    #[Test]
    public function a_rejected_request_is_left_alone_rather_than_erroring(): void
    {
        $this->apply();
        $request = LeaveRequest::where('employee_id', $this->employee->id)->firstOrFail();
        $request->update(['status' => 'rejected']);

        $this->actingAs($this->employee, 'employee')
            ->post(route('employee.leaves.cancel', $request))
            ->assertRedirect();

        $this->assertSame('rejected', $request->fresh()->status);
    }

    // ── Week-off configuration: form vs server ───────────────────────────

    /**
     * A business that has been configured to work every day: seven rows, none
     * marked off. Distinct from a business with no configuration at all.
     */
    private function configureNoWeekOffs(): void
    {
        foreach (range(0, 6) as $dow) {
            BusinessWeekOff::create([
                'business_id' => $this->business->id, 'day_of_week' => $dow, 'is_off' => false,
            ]);
        }
    }

    #[Test]
    public function a_business_that_works_every_day_has_no_week_offs(): void
    {
        // Seven rows all saying "not off" is a configuration, not an absence of
        // one — it must not fall back to the Sunday default.
        $this->configureNoWeekOffs();

        $this->assertSame([], BusinessWeekOff::offDaysFor($this->business->id));
    }

    #[Test]
    public function a_business_with_no_configuration_still_defaults_to_sunday(): void
    {
        $this->assertSame([0], BusinessWeekOff::offDaysFor($this->business->id));
    }

    #[Test]
    public function the_form_and_the_server_agree_about_week_offs(): void
    {
        // The bug: the form filtered is_off in the query and read an empty
        // result as "unconfigured", so it valued a Sunday at 0 days while the
        // server valued it at 1. A combined request could then never satisfy
        // its own validation.
        $this->configureNoWeekOffs();

        $response = $this->actingAs($this->employee, 'employee')
            ->get(route('employee.leaves.create'))
            ->assertOk();

        $formWeekOffs = $response->viewData('weekOffDays')->all();
        $serverWeekOffs = BusinessWeekOff::offDaysFor($this->business->id);

        $this->assertSame($serverWeekOffs, $formWeekOffs);
        $this->assertSame([], $formWeekOffs, 'A Sunday must not be a week-off here.');
    }

    #[Test]
    public function a_sunday_is_a_full_day_when_the_business_works_sundays(): void
    {
        $this->configureNoWeekOffs();

        // 06-09-2026 is a Sunday — the exact date from the report.
        $days = app(LeaveService::class)
            ->computeDays('2026-09-06', '2026-09-06', 'full', $this->business->id);

        $this->assertEquals(1.0, $days);
    }

    #[Test]
    public function two_half_days_can_be_combined_on_a_sunday_the_business_works(): void
    {
        // End to end: the request the employee could not submit.
        $this->configureNoWeekOffs();

        $this->actingAs($this->employee, 'employee')
            ->post(route('employee.leaves.store'), [
                'from_date' => '2026-09-06', 'to_date' => '2026-09-06',
                'day_portion' => 'full',
                'reason' => 'Half casual, half unpaid on a working Sunday',
                'splits' => [
                    ['leave_type_id' => $this->casual->id, 'days' => 0.5],
                    ['leave_type_id' => $this->lwp->id, 'days' => 0.5],
                ],
            ])->assertRedirect()->assertSessionHasNoErrors();

        $request = LeaveRequest::where('employee_id', $this->employee->id)->firstOrFail();
        $this->assertEquals(1.0, (float) $request->days);
        $this->assertTrue((bool) $request->is_combined);
    }

    #[Test]
    public function a_sunday_still_counts_as_nothing_when_it_is_a_week_off(): void
    {
        // The default behaviour must survive the fix.
        BusinessWeekOff::create([
            'business_id' => $this->business->id, 'day_of_week' => 0, 'is_off' => true,
        ]);

        $days = app(LeaveService::class)
            ->computeDays('2026-09-06', '2026-09-06', 'full', $this->business->id);

        $this->assertEquals(0.0, $days);
    }

    // ── Half-day slots on one date ───────────────────────────────────────

    #[Test]
    public function the_opposite_half_of_the_same_date_can_be_applied_for(): void
    {
        // The reported case: a first half already on file, then the second half
        // as a separate request. Two different halves of one day do not
        // overlap, so this must go through.
        $this->apply(['day_portion' => 'first_half'])->assertRedirect();

        $this->apply([
            'day_portion' => 'second_half',
            'leave_type_id' => $this->lwp->id,
            'reason' => 'Second half of the same day',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseCount('leave_requests', 2);
    }

    #[Test]
    public function it_works_in_either_order(): void
    {
        $this->apply(['day_portion' => 'second_half'])->assertRedirect();

        $this->apply([
            'day_portion' => 'first_half',
            'leave_type_id' => $this->lwp->id,
            'reason' => 'First half of the same day',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseCount('leave_requests', 2);
    }

    #[Test]
    public function the_same_half_twice_is_still_refused(): void
    {
        $this->apply(['day_portion' => 'first_half'])->assertRedirect();

        $this->apply([
            'day_portion' => 'first_half',
            'reason' => 'The very same half again',
        ])->assertSessionHas('error');

        $this->assertDatabaseCount('leave_requests', 1);
    }

    #[Test]
    public function a_full_day_is_refused_when_either_half_is_taken(): void
    {
        $this->apply(['day_portion' => 'first_half'])->assertRedirect();

        $this->apply([
            'day_portion' => 'full',
            'reason' => 'The whole day on top of a half',
        ])->assertSessionHas('error');

        $this->assertDatabaseCount('leave_requests', 1);
    }

    #[Test]
    public function a_half_day_is_refused_when_the_full_day_is_taken(): void
    {
        $this->apply(['day_portion' => 'full'])->assertRedirect();

        $this->apply([
            'day_portion' => 'second_half',
            'reason' => 'Half a day already spoken for',
        ])->assertSessionHas('error');

        $this->assertDatabaseCount('leave_requests', 1);
    }

    #[Test]
    public function a_half_day_inside_an_existing_multi_day_range_is_refused(): void
    {
        // A range is always whole days, so there is no free half inside it.
        $this->apply([
            'from_date' => '2026-09-14', 'to_date' => '2026-09-16',
            'reason' => 'Three whole days',
        ])->assertRedirect();

        $this->apply([
            'from_date' => '2026-09-16', 'to_date' => '2026-09-16',
            'day_portion' => 'second_half',
            'reason' => 'Trying to squeeze into a full-day range',
        ])->assertSessionHas('error');

        $this->assertDatabaseCount('leave_requests', 1);
    }

    #[Test]
    public function both_halves_of_one_date_can_be_approved(): void
    {
        // The approval guard has to follow the same rule, or the second half
        // would be blocked at the point HR tries to sign it off.
        $admin = Admin::create([
            'name' => 'Priya HR', 'email' => 'priya6@leave.test',
            'password' => bcrypt('password'), 'phone' => '9990007777',
            'status' => 'active', 'business_id' => $this->business->id,
        ]);
        $admin->assignRole('Admin');

        $this->apply(['day_portion' => 'first_half']);
        $this->apply([
            'day_portion' => 'second_half',
            'leave_type_id' => $this->lwp->id,
            'reason' => 'Second half of the same day',
        ]);

        foreach (LeaveRequest::orderBy('id')->get() as $request) {
            $this->actingAs($admin, 'admin')
                ->post(route('admin.hr.leaves.approve', $request), [])
                ->assertRedirect();
        }

        $this->assertSame(2, LeaveRequest::where('status', 'approved')->count());
    }

    #[Test]
    public function the_refusal_names_the_half_that_is_taken(): void
    {
        $this->apply(['day_portion' => 'first_half'])->assertRedirect();
        $this->apply(['day_portion' => 'first_half', 'reason' => 'Same half again']);

        // "already applied for this date" alone would read as though the whole
        // day were gone when only one half is.
        $this->assertStringContainsString('first half', strtolower(session('error')));
    }
}
