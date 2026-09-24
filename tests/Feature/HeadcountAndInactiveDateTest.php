<?php

namespace Tests\Feature;

use App\Exports\EmployeeReportExport;
use App\Models\Admin;
use App\Models\Business;
use App\Models\Department;
use App\Models\Employee;
use App\Services\Import\EmployeeImporter;
use App\Support\Tenancy\CurrentBusiness;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUsers;

/**
 * The HR dashboard headcount chart — the original total line plus the three
 * breakdown series added beside it — and the Inactive Date reaching all three
 * ways an employee record can be written.
 */
class HeadcountAndInactiveDateTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    private Business $business;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();

        Carbon::setTestNow(Carbon::parse('2026-09-24 10:00:00'));

        $this->business = Business::create([
            'name' => 'HC Co', 'slug' => 'hc-co',
            'currency_code' => 'INR', 'currency_symbol' => '₹',
        ]);
        app(CurrentBusiness::class)->set($this->business);

        $this->admin = Admin::create([
            'name' => 'Priya Admin', 'email' => 'priya@hc.test',
            'password' => bcrypt('password'), 'phone' => '9990001111',
            'status' => 'active', 'business_id' => $this->business->id,
        ]);
        $this->admin->assignRole('Admin');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function employee(string $code, array $attrs = []): Employee
    {
        return Employee::create(array_merge([
            'business_id' => $this->business->id, 'employee_code' => $code,
            'email' => strtolower($code).'@hc.test', 'first_name' => 'Emp', 'last_name' => $code,
            'status' => 'active', 'password' => 'secret', 'joining_date' => '2025-01-01',
        ], $attrs));
    }

    /** The trend rows the dashboard hands to the chart. */
    private function trend(): array
    {
        return $this->actingAs($this->admin, 'admin')
            ->get(route('admin.hr.dashboard'))
            ->assertOk()
            ->viewData('headcountTrend');
    }

    // ── The chart ────────────────────────────────────────────────────────

    #[Test]
    public function the_trend_still_carries_twelve_months_and_the_original_total(): void
    {
        // Long-serving, confirmed.
        $this->employee('E1', ['joining_date' => '2024-01-01', 'confirmation_date' => '2024-07-01']);

        $trend = $this->trend();

        $this->assertCount(12, $trend);
        $this->assertSame('Sep 2026', $trend[11]['label']);
        // `value` is the original headcount line and must be untouched.
        $this->assertSame(1, $trend[11]['value']);
    }

    #[Test]
    public function every_month_carries_all_four_series(): void
    {
        $this->employee('E1');

        foreach ($this->trend() as $row) {
            $this->assertArrayHasKey('value', $row);
            $this->assertArrayHasKey('active', $row);
            $this->assertArrayHasKey('inactive', $row);
            $this->assertArrayHasKey('probation', $row);
        }
    }

    #[Test]
    public function a_confirmed_employee_counts_as_active(): void
    {
        $this->employee('E1', ['joining_date' => '2024-01-01', 'confirmation_date' => '2024-07-01']);

        $september = $this->trend()[11];

        $this->assertSame(1, $september['value']);
        $this->assertSame(1, $september['active']);
        $this->assertSame(0, $september['probation']);
        $this->assertSame(0, $september['inactive']);
    }

    #[Test]
    public function someone_still_within_probation_counts_as_probation_not_active(): void
    {
        // Confirmation is in the future, so they are on probation today.
        $this->employee('E1', ['joining_date' => '2026-08-01', 'confirmation_date' => '2027-02-01']);

        $september = $this->trend()[11];

        $this->assertSame(1, $september['value']);
        $this->assertSame(0, $september['active']);
        $this->assertSame(1, $september['probation']);
    }

    #[Test]
    public function probation_is_read_month_by_month_not_from_todays_status(): void
    {
        // Confirmed on 1 Mar 2026: on probation before it, active after. The
        // `status` column says "active" for every month, so a status-based
        // chart would draw a flat line — this proves the dates are used.
        $this->employee('E1', [
            'joining_date' => '2025-10-01', 'confirmation_date' => '2026-03-01', 'status' => 'active',
        ]);

        $trend = collect($this->trend())->keyBy('label');

        $this->assertSame(1, $trend['Dec 2025']['probation'], 'Dec 2025 should still be probation');
        $this->assertSame(0, $trend['Dec 2025']['active']);

        $this->assertSame(0, $trend['Jun 2026']['probation'], 'Jun 2026 should be past probation');
        $this->assertSame(1, $trend['Jun 2026']['active']);
    }

    // ── Status is the authority (the live-data bugs) ─────────────────────

    #[Test]
    public function an_employee_marked_inactive_with_no_leaving_date_still_counts_as_inactive(): void
    {
        // On live data all 150 inactive employees had no last_working_date, so
        // a date-only rule read them as still on the roll: Total and Active
        // came out identical and the Inactive line never rendered.
        $this->employee('E1', [
            'joining_date' => '2024-01-01', 'confirmation_date' => '2024-07-01',
            'status' => 'inactive', 'last_working_date' => null,
        ]);

        $september = $this->trend()[11];

        $this->assertSame(1, $september['value']);
        $this->assertSame(1, $september['inactive']);
        $this->assertSame(0, $september['active']);
    }

    #[Test]
    public function marked_probation_wins_over_a_confirmation_date_that_has_passed(): void
    {
        // 20 of the 25 on probation had a confirmation date already in the
        // past, so the chart showed 5 instead of 25.
        $this->employee('E1', [
            'joining_date' => '2025-01-01',
            'confirmation_date' => '2025-07-01',   // already passed
            'status' => 'probation',
        ]);

        $september = $this->trend()[11];

        $this->assertSame(1, $september['probation']);
        $this->assertSame(0, $september['active']);
    }

    #[Test]
    public function the_three_series_add_up_on_a_realistic_mix(): void
    {
        foreach (range(1, 6) as $i) {
            $this->employee('A'.$i, ['joining_date' => '2024-01-01', 'confirmation_date' => '2024-07-01']);
        }
        foreach (range(1, 3) as $i) {
            $this->employee('P'.$i, ['joining_date' => '2026-01-01', 'confirmation_date' => '2026-03-01', 'status' => 'probation']);
        }
        foreach (range(1, 4) as $i) {
            $this->employee('I'.$i, ['joining_date' => '2024-01-01', 'status' => 'inactive']);
        }

        $september = $this->trend()[11];

        $this->assertSame(13, $september['value']);
        $this->assertSame(6, $september['active']);
        $this->assertSame(3, $september['probation']);
        $this->assertSame(4, $september['inactive']);
    }

    #[Test]
    public function a_dated_departure_still_beats_the_status_for_earlier_months(): void
    {
        // Where a leaving date IS recorded it dates the change precisely, so
        // history stays right rather than marking them inactive all year.
        $this->employee('E1', [
            'joining_date' => '2024-01-01', 'confirmation_date' => '2024-07-01',
            'last_working_date' => '2026-07-31', 'status' => 'inactive',
        ]);

        $trend = collect($this->trend())->keyBy('label');

        $this->assertSame(1, $trend['Mar 2026']['active'], 'Still working in March');
        $this->assertSame(0, $trend['Mar 2026']['inactive']);
        $this->assertSame(1, $trend['Sep 2026']['inactive'], 'Gone by September');
    }

    #[Test]
    public function a_leaver_moves_from_active_to_inactive_within_the_total(): void
    {
        $this->employee('E1', [
            'joining_date' => '2025-01-01', 'confirmation_date' => '2025-07-01',
            'last_working_date' => '2026-05-31', 'status' => 'inactive',
        ]);

        $trend = collect($this->trend())->keyBy('label');

        // Total counts everyone on the books, so it does not dip when someone
        // leaves — they move across from Active to Inactive.
        $this->assertSame(1, $trend['Apr 2026']['value']);
        $this->assertSame(1, $trend['Apr 2026']['active']);
        $this->assertSame(0, $trend['Apr 2026']['inactive']);

        $this->assertSame(1, $trend['Jun 2026']['value']);
        $this->assertSame(0, $trend['Jun 2026']['active']);
        $this->assertSame(1, $trend['Jun 2026']['inactive']);
    }

    #[Test]
    public function the_total_equals_active_plus_inactive_plus_probation(): void
    {
        $this->employee('A', ['joining_date' => '2024-01-01', 'confirmation_date' => '2024-07-01']);
        $this->employee('B', ['joining_date' => '2026-08-01', 'status' => 'probation']);
        $this->employee('C', ['joining_date' => '2024-01-01', 'last_working_date' => '2026-01-31', 'status' => 'inactive']);

        foreach ($this->trend() as $row) {
            $this->assertSame(
                $row['value'],
                $row['active'] + $row['inactive'] + $row['probation'],
                "Total should equal active + inactive + probation for {$row['label']}",
            );
        }
    }

    #[Test]
    public function someone_who_has_not_joined_yet_counts_nowhere(): void
    {
        $this->employee('E1', ['joining_date' => '2026-09-01']);

        $trend = collect($this->trend())->keyBy('label');

        $this->assertSame(0, $trend['Aug 2026']['value']);
        $this->assertSame(0, $trend['Aug 2026']['inactive']);
        $this->assertSame(1, $trend['Sep 2026']['value']);
    }

    // ── Inactive Date, all three routes ──────────────────────────────────

    #[Test]
    public function the_manual_form_offers_the_inactive_date(): void
    {
        $employee = $this->employee('E1');

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.hr.employees.edit', $employee))
            ->assertOk()
            ->assertSee('name="last_working_date"', false)
            ->assertSee('Inactive Date');
    }

    #[Test]
    public function the_create_form_offers_it_too(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.hr.employees.create'))
            ->assertOk()
            ->assertSee('name="last_working_date"', false);
    }

    #[Test]
    public function a_manually_entered_inactive_date_is_saved(): void
    {
        $employee = $this->employee('E1');

        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.hr.employees.update', $employee), [
                'employee_code' => 'E1',
                'first_name' => 'Emp', 'last_name' => 'E1',
                'email' => 'e1@hc.test', 'joining_date' => '2025-01-01',
                'employment_type' => 'full_time', 'work_mode' => 'on_site',
                'status' => 'inactive',
                'last_working_date' => '2026-09-20',
            ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertSame('2026-09-20', $employee->fresh()->last_working_date->toDateString());
    }

    #[Test]
    public function the_import_template_and_export_both_carry_the_column(): void
    {
        $this->assertContains('Inactive Date', app(EmployeeImporter::class)->templateHeaders());
        $this->assertContains('Inactive Date', (new EmployeeReportExport([]))->headings());

        // The template's sample row must stay the same width as its headers.
        $importer = app(EmployeeImporter::class);
        $this->assertCount(count($importer->templateHeaders()), $importer->sampleRow());
    }

    #[Test]
    public function the_export_row_is_the_same_width_as_its_headings(): void
    {
        $this->employee('E1', ['last_working_date' => '2026-09-20']);

        $export = new EmployeeReportExport([]);
        $rows = $export->collection();

        $this->assertCount(count($export->headings()), $rows->first());
        $this->assertContains('2026-09-20', $rows->first());
    }

    #[Test]
    public function an_import_that_supplies_a_past_inactive_date_marks_the_person_inactive(): void
    {
        Department::create(['business_id' => $this->business->id, 'name' => 'Ops', 'code' => 'OPS']);

        app(EmployeeImporter::class)->importRow([
            'employee code' => 'IMP-1',
            'first name' => 'Ravi',
            'last name' => 'Kumar',
            'email' => 'ravi@hc.test',
            'department' => 'Ops',
            'joining date' => '2025-01-01',
            'inactive date' => '2026-06-30',
            'employment type' => 'full_time',
        ], $this->business->id);

        $employee = Employee::where('employee_code', 'IMP-1')->firstOrFail();

        $this->assertSame('2026-06-30', $employee->last_working_date->toDateString());
        $this->assertSame('inactive', $employee->status);
    }

    #[Test]
    public function an_import_with_no_inactive_date_still_creates_an_active_employee(): void
    {
        app(EmployeeImporter::class)->importRow([
            'employee code' => 'IMP-2',
            'first name' => 'Sita',
            'email' => 'sita@hc.test',
            'joining date' => '2025-01-01',
            'inactive date' => '',
            'employment type' => 'full_time',
        ], $this->business->id);

        $employee = Employee::where('employee_code', 'IMP-2')->firstOrFail();

        $this->assertNull($employee->last_working_date);
        $this->assertSame('active', $employee->status);
    }
}
