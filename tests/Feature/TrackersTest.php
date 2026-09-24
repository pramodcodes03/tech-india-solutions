<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\BreakSheet;
use App\Models\Business;
use App\Models\Department;
use App\Models\DieselBudget;
use App\Models\DieselEntry;
use App\Models\Employee;
use App\Models\TrackerOption;
use App\Models\VisitorLog;
use App\Services\Import\BreakSheetImporter;
use App\Services\Import\DieselEntryImporter;
use App\Services\Import\VisitorLogImporter;
use App\Services\Tracker\TrackerAnalyticsService;
use App\Support\Tenancy\CurrentBusiness;
use App\Support\TrackerFilter;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUsers;

/**
 * Module B — the three operational trackers.
 */
class TrackersTest extends TestCase
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
            'name' => 'Tracker Co', 'slug' => 'tracker-co',
            'currency_code' => 'INR', 'currency_symbol' => '₹',
        ]);
        app(CurrentBusiness::class)->set($this->business);

        $this->admin = Admin::create([
            'name' => 'Admin', 'email' => 'admin@trackers.test',
            'password' => bcrypt('password'), 'phone' => '9990001111',
            'status' => 'active', 'business_id' => $this->business->id,
        ]);
        $this->admin->assignRole('Admin');

        $department = Department::create([
            'business_id' => $this->business->id, 'name' => 'Operations', 'code' => 'OPS',
        ]);

        $this->employee = Employee::create([
            'business_id' => $this->business->id, 'employee_code' => 'EMP-001',
            'email' => 'asha@trackers.test', 'first_name' => 'Asha', 'last_name' => 'Verma',
            'department_id' => $department->id,
            'status' => 'active', 'password' => 'secret', 'joining_date' => '2025-01-01',
        ]);
    }

    private function asAdmin()
    {
        return $this->actingAs($this->admin, 'admin');
    }

    private function option(string $type, string $name): TrackerOption
    {
        return TrackerOption::create([
            'business_id' => $this->business->id,
            'type' => $type, 'name' => $name, 'sort_order' => 1, 'is_active' => true,
        ]);
    }

    private function filterForThisMonth(): TrackerFilter
    {
        return TrackerFilter::fromRequest(new Request(['period' => 'month', 'month' => now()->format('Y-m')]));
    }

    // ── Hub & navigation ─────────────────────────────────────────────────

    #[Test]
    public function tracker_hub_and_every_register_loads(): void
    {
        foreach ([
            'admin.hr.trackers.index',
            'admin.hr.trackers.break.index',
            'admin.hr.trackers.break.analytics',
            'admin.hr.trackers.diesel.index',
            'admin.hr.trackers.diesel.analytics',
            'admin.hr.trackers.diesel.budgets',
            'admin.hr.trackers.visitors.index',
            'admin.hr.trackers.visitors.analytics',
            'admin.hr.trackers.options.index',
        ] as $route) {
            $this->asAdmin()->get(route($route))->assertStatus(200);
        }
    }

    #[Test]
    public function every_register_renders_with_data_present(): void
    {
        $this->makeBreak('13:30', '14:05');
        $this->makeBreak('23:40', '00:25');
        DieselEntry::create([
            'business_id' => $this->business->id, 'serial_no' => 'DSL-R1',
            'entry_date' => now()->toDateString(), 'quantity' => 40, 'amount' => 4000, 'rate_per_litre' => 100,
        ]);
        DieselBudget::create([
            'business_id' => $this->business->id,
            'period_month' => now()->startOfMonth()->toDateString(), 'amount' => 10000,
        ]);
        VisitorLog::create([
            'business_id' => $this->business->id, 'visit_date' => now()->toDateString(),
            'visitor_name' => 'Ravi Kumar', 'availability_status' => 'available', 'outcome' => 'selected',
        ]);

        $this->asAdmin()->get(route('admin.hr.trackers.index'))->assertStatus(200);

        $this->asAdmin()->get(route('admin.hr.trackers.break.index'))
            ->assertStatus(200)->assertSee('45m');

        $this->asAdmin()->get(route('admin.hr.trackers.break.analytics'))->assertStatus(200);

        $this->asAdmin()->get(route('admin.hr.trackers.diesel.index'))
            ->assertStatus(200)->assertSee('DSL-R1');

        $this->asAdmin()->get(route('admin.hr.trackers.diesel.analytics'))->assertStatus(200);
        $this->asAdmin()->get(route('admin.hr.trackers.diesel.budgets'))->assertStatus(200);

        $this->asAdmin()->get(route('admin.hr.trackers.visitors.index'))
            ->assertStatus(200)->assertSee('Ravi Kumar');

        $this->asAdmin()->get(route('admin.hr.trackers.visitors.analytics'))->assertStatus(200);
        $this->asAdmin()->get(route('admin.hr.trackers.options.index'))->assertStatus(200);
    }

    #[Test]
    public function an_admin_without_tracker_permissions_is_refused(): void
    {
        $outsider = Admin::create([
            'name' => 'Sales', 'email' => 'sales@trackers.test',
            'password' => bcrypt('password'), 'phone' => '9990002222',
            'status' => 'active', 'business_id' => $this->business->id,
        ]);
        $outsider->assignRole('Sales');

        $this->actingAs($outsider, 'admin')
            ->get(route('admin.hr.trackers.break.index'))
            ->assertStatus(403);

        $this->actingAs($outsider, 'admin')
            ->get(route('admin.hr.trackers.index'))
            ->assertStatus(403);
    }

    // ── Break Sheet ──────────────────────────────────────────────────────

    #[Test]
    public function break_entry_stores_and_auto_calculates_total_timing(): void
    {
        $type = $this->option(TrackerOption::TYPE_BREAK, 'Lunch Break');

        $this->asAdmin()->post(route('admin.hr.trackers.break.store'), [
            'employee_id' => $this->employee->id,
            'break_date' => now()->toDateString(),
            'out_time' => '13:30',
            'in_time' => '14:05',
            'break_type_id' => $type->id,
            'remarks' => 'Canteen',
        ])->assertRedirect(route('admin.hr.trackers.break.index'));

        $entry = BreakSheet::first();
        $this->assertNotNull($entry);
        $this->assertSame(35, $entry->duration_minutes);
        $this->assertSame('35m', $entry->duration_label);
        $this->assertSame($this->admin->id, $entry->recorded_by);
    }

    #[Test]
    public function a_break_crossing_midnight_is_not_negative(): void
    {
        $this->assertSame(45, BreakSheet::minutesBetween('23:40', '00:25'));
        $this->assertSame(90, BreakSheet::minutesBetween('01:00', '02:30'));
        $this->assertNull(BreakSheet::minutesBetween('13:00', null));
    }

    #[Test]
    public function a_break_still_open_has_no_duration(): void
    {
        $this->asAdmin()->post(route('admin.hr.trackers.break.store'), [
            'employee_id' => $this->employee->id,
            'break_date' => now()->toDateString(),
            'out_time' => '11:00',
        ])->assertRedirect();

        $this->assertNull(BreakSheet::first()->duration_minutes);
    }

    #[Test]
    public function a_future_dated_break_is_rejected(): void
    {
        $this->asAdmin()->post(route('admin.hr.trackers.break.store'), [
            'employee_id' => $this->employee->id,
            'break_date' => now()->addDay()->toDateString(),
            'out_time' => '11:00',
            'in_time' => '11:20',
        ])->assertSessionHasErrors('break_date');

        $this->assertSame(0, BreakSheet::count());
    }

    #[Test]
    public function break_analytics_aggregates_by_employee_and_department(): void
    {
        $this->makeBreak('10:00', '10:30');   // 30
        $this->makeBreak('14:00', '15:00');   // 60

        $data = app(TrackerAnalyticsService::class)->breaks($this->filterForThisMonth());

        $this->assertSame(2, $data['totals']['entries']);
        $this->assertSame(90, $data['totals']['minutes']);
        $this->assertSame(45.0, $data['totals']['avg_minutes']);
        $this->assertSame(90, (int) $data['per_employee']->first()->total_minutes);
        $this->assertSame('Operations', $data['per_department']->first()->department);
        $this->assertSame(60, (int) $data['longest']->first()->duration_minutes);
    }

    #[Test]
    public function breaks_are_grouped_per_employee_per_day_into_a_combined_total(): void
    {
        // The four segments from the sheet HR works from: 5 + 15 + 16 + 16.
        $this->makeBreak('10:07', '10:12');   //  5
        $this->makeBreak('11:05', '11:20');   // 15
        $this->makeBreak('14:32', '14:48');   // 16
        $this->makeBreak('15:12', '15:28');   // 16

        $totals = BreakSheet::dailyTotals(BreakSheet::query());
        $key = BreakSheet::groupKey($this->employee->id, now()->toDateString());

        $this->assertSame(52, $totals[$key]['minutes'], 'the four segments add to 52 minutes');
        $this->assertSame(4, $totals[$key]['entries']);
        $this->assertSame('52m', BreakSheet::formatMinutes($totals[$key]['minutes']));
    }

    #[Test]
    public function a_days_total_is_kept_separate_from_another_days(): void
    {
        $this->makeBreak('10:00', '10:30');                                    // today, 30
        $this->makeBreak('11:00', '11:15', now()->subDay()->toDateString());   // yesterday, 15

        $totals = BreakSheet::dailyTotals(BreakSheet::query());

        $today = BreakSheet::groupKey($this->employee->id, now()->toDateString());
        $yesterday = BreakSheet::groupKey($this->employee->id, now()->subDay()->toDateString());

        $this->assertSame(30, $totals[$today]['minutes']);
        $this->assertSame(15, $totals[$yesterday]['minutes']);
    }

    #[Test]
    public function an_open_break_does_not_break_the_days_total(): void
    {
        $this->makeBreak('10:00', '10:30');   // 30
        $this->makeBreak('14:00', null);      // still out — no duration yet

        $totals = BreakSheet::dailyTotals(BreakSheet::query());
        $key = BreakSheet::groupKey($this->employee->id, now()->toDateString());

        $this->assertSame(30, $totals[$key]['minutes'], 'the open segment contributes nothing');
        $this->assertSame(2, $totals[$key]['entries']);
    }

    #[Test]
    public function the_register_shows_the_combined_total_for_the_day(): void
    {
        $this->makeBreak('10:07', '10:12');
        $this->makeBreak('11:05', '11:20');
        $this->makeBreak('14:32', '14:48');
        $this->makeBreak('15:12', '15:28');

        $this->asAdmin()
            ->get(route('admin.hr.trackers.break.index'))
            ->assertOk()
            ->assertSee('Total Break Timing')
            ->assertSee('52m');
    }

    #[Test]
    public function analytics_reports_a_row_per_employee_per_day(): void
    {
        $this->makeBreak('10:00', '10:30');                                    // today, 30
        $this->makeBreak('14:00', '14:20');                                    // today, 20
        $this->makeBreak('11:00', '11:15', now()->subDay()->toDateString());   // yesterday, 15

        $data = app(TrackerAnalyticsService::class)->breaks($this->filterForThisMonth());
        $rows = $data['per_employee_day'];

        $this->assertCount(2, $rows, 'one row per employee per day, not per segment');

        $today = $rows->firstWhere(
            fn ($r) => substr((string) $r->break_date, 0, 10) === now()->toDateString(),
        );

        $this->assertSame(50, (int) $today->total_minutes);
        $this->assertSame(2, (int) $today->entries);
    }

    #[Test]
    public function re_importing_the_same_sheet_corrects_rather_than_doubling_the_total(): void
    {
        // A break sheet sent twice — a retry, a double submit, the same file
        // uploaded again. Before the importer keyed its writes this doubled
        // every row, and so doubled the day's combined total.
        $row = [
            'employee code' => $this->employee->employee_code,
            'break date' => now()->toDateString(),
            'out time' => '10:07',
            'in time' => '10:12',
            'break type' => 'Break',
            'remarks' => '',
        ];

        $importer = app(BreakSheetImporter::class);
        $importer->importRow($row, $this->business->id);
        $importer->importRow($row, $this->business->id);

        $this->assertSame(1, BreakSheet::count(), 'the second import updates the first row');

        $totals = BreakSheet::dailyTotals(BreakSheet::query());
        $key = BreakSheet::groupKey($this->employee->id, now()->toDateString());

        $this->assertSame(5, $totals[$key]['minutes'], 'not 10 — the day is not double-counted');
    }

    #[Test]
    public function a_re_import_with_a_corrected_in_time_updates_the_duration(): void
    {
        $importer = app(BreakSheetImporter::class);
        $base = [
            'employee code' => $this->employee->employee_code,
            'break date' => now()->toDateString(),
            'out time' => '10:07',
            'break type' => 'Break',
            'remarks' => '',
        ];

        $importer->importRow($base + ['in time' => '10:12'], $this->business->id);
        $importer->importRow($base + ['in time' => '10:30'], $this->business->id);

        $this->assertSame(1, BreakSheet::count());
        $this->assertSame(23, BreakSheet::first()->duration_minutes);
    }

    #[Test]
    public function the_database_itself_refuses_a_duplicated_break(): void
    {
        // The importer keys its writes, but a keyed updateOrCreate is a SELECT
        // then an INSERT — two of them racing can both miss. The unique key is
        // what makes a duplicate actually impossible rather than merely
        // unlikely, so assert on the constraint and not just on the importer.
        $this->makeBreak('10:07', '10:12');

        $this->expectException(UniqueConstraintViolationException::class);

        $this->makeBreak('10:07', '10:12');
    }

    #[Test]
    public function a_break_at_a_time_already_recorded_is_rejected_with_a_message(): void
    {
        // …and because the constraint is real, the manual form has to fail on
        // it gracefully instead of surfacing an integrity error as a 500.
        $this->makeBreak('13:30', '14:05');

        $this->asAdmin()->post(route('admin.hr.trackers.break.store'), [
            'employee_id' => $this->employee->id,
            'break_date' => now()->toDateString(),
            'out_time' => '13:30',
            'in_time' => '14:20',
        ])->assertSessionHasErrors('out_time');

        $this->assertSame(1, BreakSheet::count());
    }

    #[Test]
    public function editing_a_break_without_moving_its_out_time_is_still_allowed(): void
    {
        // The duplicate guard must not mistake a row for a clash with itself.
        $entry = $this->makeBreak('13:30', '14:05');

        $this->asAdmin()->put(route('admin.hr.trackers.break.update', $entry), [
            'employee_id' => $this->employee->id,
            'break_date' => now()->toDateString(),
            'out_time' => '13:30',
            'in_time' => '14:20',
        ])->assertRedirect(route('admin.hr.trackers.break.index'));

        $this->assertSame(50, $entry->fresh()->duration_minutes);
    }

    // ── Diesel ───────────────────────────────────────────────────────────

    #[Test]
    public function diesel_entry_stores_rate_and_slip_upload(): void
    {
        Storage::fake('public');

        $this->asAdmin()->post(route('admin.hr.trackers.diesel.store'), [
            'serial_no' => DieselEntry::nextSerial(),
            'entry_date' => now()->toDateString(),
            'entry_time' => '10:15',
            'bill_no' => 'B-4471',
            'slip_no' => 'S-119',
            'vehicle_no' => 'MH12AB1234',
            'quantity' => 40,
            'amount' => 4000,
            'attachment' => UploadedFile::fake()->create('slip.pdf', 120, 'application/pdf'),
        ])->assertRedirect(route('admin.hr.trackers.diesel.index'));

        $entry = DieselEntry::first();
        $this->assertSame('100.00', (string) $entry->rate_per_litre);
        $this->assertNotNull($entry->attachment);
        Storage::disk('public')->assertExists($entry->attachment);
    }

    #[Test]
    public function diesel_serial_numbers_are_sequential_within_a_month(): void
    {
        $first = DieselEntry::nextSerial(now()->toDateString());
        $this->assertSame('DSL-'.now()->format('Ym').'-0001', $first);

        DieselEntry::create([
            'business_id' => $this->business->id, 'serial_no' => $first,
            'entry_date' => now()->toDateString(), 'quantity' => 10, 'amount' => 1000,
        ]);

        $this->assertSame('DSL-'.now()->format('Ym').'-0002', DieselEntry::nextSerial(now()->toDateString()));
    }

    #[Test]
    public function a_duplicate_diesel_serial_is_rejected(): void
    {
        DieselEntry::create([
            'business_id' => $this->business->id, 'serial_no' => 'DSL-001',
            'entry_date' => now()->toDateString(), 'quantity' => 10, 'amount' => 1000,
        ]);

        $this->asAdmin()->post(route('admin.hr.trackers.diesel.store'), [
            'serial_no' => 'DSL-001',
            'entry_date' => now()->toDateString(),
            'quantity' => 5, 'amount' => 500,
        ])->assertSessionHasErrors('serial_no');

        $this->assertSame(1, DieselEntry::count());
    }

    #[Test]
    public function diesel_budget_reports_allocated_consumed_and_remaining(): void
    {
        DieselBudget::create([
            'business_id' => $this->business->id,
            'period_month' => now()->startOfMonth()->toDateString(),
            'amount' => 10000,
        ]);

        DieselEntry::create([
            'business_id' => $this->business->id, 'serial_no' => 'DSL-A',
            'entry_date' => now()->toDateString(), 'quantity' => 30, 'amount' => 3000,
        ]);

        $budget = app(TrackerAnalyticsService::class)->dieselBudget($this->filterForThisMonth());

        $this->assertTrue($budget['has_budget']);
        $this->assertSame(10000.0, $budget['allocated']);
        $this->assertSame(3000.0, $budget['consumed']);
        $this->assertSame(7000.0, $budget['remaining']);
        $this->assertSame(30.0, $budget['percent']);
    }

    #[Test]
    public function saving_a_budget_twice_revises_it_instead_of_duplicating(): void
    {
        $month = now()->format('Y-m');

        $this->asAdmin()->post(route('admin.hr.trackers.diesel.budgets.store'), ['period_month' => $month, 'amount' => 5000]);
        $this->asAdmin()->post(route('admin.hr.trackers.diesel.budgets.store'), ['period_month' => $month, 'amount' => 8000]);

        $this->assertSame(1, DieselBudget::count());
        $this->assertSame('8000.00', (string) DieselBudget::first()->amount);
    }

    // ── Visitors ─────────────────────────────────────────────────────────

    #[Test]
    public function visitor_entry_stores_and_conversion_is_measured_against_attendance(): void
    {
        $source = $this->option(TrackerOption::TYPE_SOURCE, 'Naukri');
        $purpose = $this->option(TrackerOption::TYPE_PURPOSE, 'Interview');

        $this->asAdmin()->post(route('admin.hr.trackers.visitors.store'), [
            'visit_date' => now()->toDateString(),
            'visitor_name' => 'Ravi Kumar',
            'mobile' => '9876543210',
            'source_id' => $source->id,
            'purpose_id' => $purpose->id,
            'arrival_time' => '10:30',
            'called_by' => 'Priya',
            'interview_by' => 'Amit',
            'availability_status' => 'available',
            'outcome' => 'selected',
        ])->assertRedirect(route('admin.hr.trackers.visitors.index'));

        // A no-show must not drag the conversion rate down — it never got tested.
        VisitorLog::create([
            'business_id' => $this->business->id,
            'visit_date' => now()->toDateString(),
            'visitor_name' => 'Absent Person',
            'source_id' => $source->id,
            'availability_status' => 'no_show',
            'outcome' => 'pending',
        ]);

        $data = app(TrackerAnalyticsService::class)->visitors($this->filterForThisMonth());

        $this->assertSame(2, $data['totals']['visits']);
        $this->assertSame(1, $data['totals']['attended']);
        $this->assertSame(1, $data['totals']['selected']);
        $this->assertSame(100.0, $data['totals']['conversion']);
        $this->assertSame(50.0, $data['totals']['attendance_rate']);
        $this->assertSame(2, (int) $data['by_source']->firstWhere('label', 'Naukri')->total);
    }

    #[Test]
    public function an_unknown_availability_status_is_rejected(): void
    {
        $this->asAdmin()->post(route('admin.hr.trackers.visitors.store'), [
            'visit_date' => now()->toDateString(),
            'visitor_name' => 'Someone',
            'availability_status' => 'made_up',
            'outcome' => 'pending',
        ])->assertSessionHasErrors('availability_status');
    }

    // ── Period filter ────────────────────────────────────────────────────

    #[Test]
    public function the_period_filter_narrows_each_mode_correctly(): void
    {
        $this->makeBreak('10:00', '10:30', now()->toDateString());
        $this->makeBreak('10:00', '10:30', now()->subMonth()->startOfMonth()->toDateString());

        $thisMonth = TrackerFilter::fromRequest(new Request(['period' => 'month', 'month' => now()->format('Y-m')]));
        $this->assertSame(1, $thisMonth->apply(BreakSheet::query(), 'break_date')->count());

        $thisYear = TrackerFilter::fromRequest(new Request(['period' => 'year', 'year' => now()->format('Y')]));
        $this->assertSame(
            now()->subMonth()->year === now()->year ? 2 : 1,
            $thisYear->apply(BreakSheet::query(), 'break_date')->count(),
        );

        $today = TrackerFilter::fromRequest(new Request(['period' => 'date', 'date' => now()->toDateString()]));
        $this->assertSame(1, $today->apply(BreakSheet::query(), 'break_date')->count());

        $all = TrackerFilter::fromRequest(new Request(['period' => 'all']));
        $this->assertSame(2, $all->apply(BreakSheet::query(), 'break_date')->count());
    }

    #[Test]
    public function a_backwards_date_range_is_swapped_rather_than_returning_nothing(): void
    {
        $this->makeBreak('10:00', '10:30', now()->toDateString());

        $filter = TrackerFilter::fromRequest(new Request([
            'period' => 'range',
            'from' => now()->addDays(3)->toDateString(),
            'to' => now()->subDays(3)->toDateString(),
        ]));

        $this->assertSame(1, $filter->apply(BreakSheet::query(), 'break_date')->count());
    }

    // ── Exports ──────────────────────────────────────────────────────────

    #[Test]
    public function each_register_exports_to_excel_and_pdf(): void
    {
        $this->makeBreak('10:00', '10:30');
        DieselEntry::create([
            'business_id' => $this->business->id, 'serial_no' => 'DSL-X',
            'entry_date' => now()->toDateString(), 'quantity' => 10, 'amount' => 1000,
        ]);
        VisitorLog::create([
            'business_id' => $this->business->id, 'visit_date' => now()->toDateString(),
            'visitor_name' => 'Guest', 'availability_status' => 'available', 'outcome' => 'pending',
        ]);

        foreach (['break', 'diesel', 'visitors'] as $register) {
            foreach (['excel', 'pdf'] as $format) {
                $this->asAdmin()
                    ->get(route("admin.hr.trackers.{$register}.export", ['format' => $format]))
                    ->assertStatus(200);
            }
        }
    }

    // ── Tracker settings ─────────────────────────────────────────────────

    #[Test]
    public function an_option_in_use_is_deactivated_rather_than_deleted(): void
    {
        $type = $this->option(TrackerOption::TYPE_BREAK, 'Tea Break');
        $this->makeBreak('10:00', '10:30', null, $type->id);

        $this->asAdmin()->delete(route('admin.hr.trackers.options.destroy', $type));

        $this->assertNotNull($type->fresh(), 'The option should still exist.');
        $this->assertFalse($type->fresh()->is_active);
    }

    #[Test]
    public function an_unused_option_is_deleted_outright(): void
    {
        $type = $this->option(TrackerOption::TYPE_SOURCE, 'Walk-in');

        $this->asAdmin()->delete(route('admin.hr.trackers.options.destroy', $type));

        $this->assertNull($type->fresh());
    }

    #[Test]
    public function a_duplicate_option_name_is_refused(): void
    {
        $this->option(TrackerOption::TYPE_SOURCE, 'Referral');

        $this->asAdmin()->post(route('admin.hr.trackers.options.store'), [
            'type' => TrackerOption::TYPE_SOURCE, 'name' => 'Referral',
        ])->assertSessionHas('error');

        $this->assertSame(1, TrackerOption::ofType(TrackerOption::TYPE_SOURCE)->count());
    }

    // ── Excel import ─────────────────────────────────────────────────────

    #[Test]
    public function the_break_sheet_importer_validates_and_imports_rows(): void
    {
        $importer = app(BreakSheetImporter::class);

        $bad = $importer->validateRow([
            'employee code' => 'NOPE', 'break date' => 'not-a-date', 'out time' => '99:99',
        ], $this->business->id);
        $this->assertCount(3, $bad);

        $good = ['employee code' => 'EMP-001', 'break date' => now()->toDateString(),
            'out time' => '13:30', 'in time' => '14:05', 'break type' => 'Field Visit', 'remarks' => ''];
        $this->assertSame([], $importer->validateRow($good, $this->business->id));

        $importer->importRow($good, $this->business->id);

        $entry = BreakSheet::first();
        $this->assertSame(35, $entry->duration_minutes);
        // A break type not yet configured is created on the fly by the import.
        $this->assertSame('Field Visit', $entry->breakType->name);
    }

    #[Test]
    public function the_diesel_importer_auto_numbers_a_blank_serial(): void
    {
        $importer = app(DieselEntryImporter::class);

        $row = ['serial no' => '', 'date' => now()->format('d-m-Y'), 'time' => '10:15',
            'bill no' => 'B-1', 'slip no' => 'S-1', 'vehicle no' => 'MH12', 'quantity' => '35.50',
            'amount' => '3,550.00', 'remarks' => ''];

        $this->assertSame([], $importer->validateRow($row, $this->business->id));
        $importer->importRow($row, $this->business->id);

        $entry = DieselEntry::first();
        $this->assertSame('DSL-'.now()->format('Ym').'-0001', $entry->serial_no);
        $this->assertSame('3550.00', (string) $entry->amount);
        $this->assertSame('100.00', (string) $entry->rate_per_litre);
    }

    #[Test]
    public function the_visitor_importer_accepts_labels_or_keys_for_status(): void
    {
        $importer = app(VisitorLogImporter::class);

        $row = ['date of visit' => now()->toDateString(), 'visitor name' => 'Ravi',
            'mobile' => '9876543210', 'source' => 'Naukri', 'arrival time' => '10:30',
            'purpose' => 'Interview', 'called by' => 'Priya', 'interview by' => 'Amit',
            'availability status' => 'No Show', 'outcome' => 'rejected', 'remarks' => ''];

        $this->assertSame([], $importer->validateRow($row, $this->business->id));
        $importer->importRow($row, $this->business->id);

        $log = VisitorLog::first();
        $this->assertSame('no_show', $log->availability_status);
        $this->assertSame('rejected', $log->outcome);
        $this->assertSame('Naukri', $log->source->name);

        $row['availability status'] = 'invented';
        $this->assertNotEmpty($importer->validateRow($row, $this->business->id));
    }

    // ── Tenancy ──────────────────────────────────────────────────────────

    #[Test]
    public function registers_are_scoped_to_the_active_business(): void
    {
        $other = Business::create([
            'name' => 'Other Co', 'slug' => 'other-co',
            'currency_code' => 'INR', 'currency_symbol' => '₹',
        ]);

        DieselEntry::create([
            'business_id' => $other->id, 'serial_no' => 'DSL-OTHER',
            'entry_date' => now()->toDateString(), 'quantity' => 10, 'amount' => 1000,
        ]);
        DieselEntry::create([
            'business_id' => $this->business->id, 'serial_no' => 'DSL-MINE',
            'entry_date' => now()->toDateString(), 'quantity' => 10, 'amount' => 1000,
        ]);

        $this->assertSame(1, DieselEntry::count());
        $this->assertSame('DSL-MINE', DieselEntry::first()->serial_no);
    }

    // ── Bulk delete ──────────────────────────────────────────────────────

    #[Test]
    public function ticked_break_entries_are_deleted_together(): void
    {
        $keep = $this->makeBreak('09:00', '09:15');
        $a = $this->makeBreak('10:00', '10:30');
        $b = $this->makeBreak('11:00', '11:30');

        $this->asAdmin()
            ->delete(route('admin.hr.trackers.break.bulk-destroy'), ['ids' => [$a->id, $b->id]])
            ->assertRedirect();

        $this->assertSame([$keep->id], BreakSheet::pluck('id')->all());
    }

    #[Test]
    public function a_rows_own_delete_button_beats_the_ticked_boxes(): void
    {
        // The row button and the checkboxes post through one form, so both
        // arrive together. Pressing Delete on a row must delete that row.
        $row = $this->makeBreak('09:00', '09:15');
        $ticked = $this->makeBreak('10:00', '10:30');

        $this->asAdmin()->delete(route('admin.hr.trackers.break.bulk-destroy'), [
            'single_id' => $row->id,
            'ids' => [$ticked->id],
        ])->assertRedirect();

        $this->assertSame([$ticked->id], BreakSheet::pluck('id')->all());
    }

    #[Test]
    public function bulk_delete_with_nothing_selected_changes_nothing(): void
    {
        $this->makeBreak('10:00', '10:30');

        $this->asAdmin()
            ->delete(route('admin.hr.trackers.break.bulk-destroy'), ['ids' => []])
            ->assertSessionHasErrors('ids');

        $this->assertSame(1, BreakSheet::count());
    }

    #[Test]
    public function bulk_delete_is_refused_without_the_delete_permission(): void
    {
        $entry = $this->makeBreak('10:00', '10:30');

        $outsider = Admin::create([
            'name' => 'Sales', 'email' => 'sales-bulk@trackers.test',
            'password' => bcrypt('password'), 'phone' => '9990004444',
            'status' => 'active', 'business_id' => $this->business->id,
        ]);
        $outsider->assignRole('Sales');

        $this->actingAs($outsider, 'admin')
            ->delete(route('admin.hr.trackers.break.bulk-destroy'), ['ids' => [$entry->id]])
            ->assertStatus(403);

        $this->assertSame(1, BreakSheet::count());
    }

    #[Test]
    public function bulk_deleting_diesel_entries_removes_their_uploaded_slips(): void
    {
        Storage::fake('public');

        $this->asAdmin()->post(route('admin.hr.trackers.diesel.store'), [
            'serial_no' => DieselEntry::nextSerial(),
            'entry_date' => now()->toDateString(),
            'quantity' => 40, 'amount' => 4000,
            'attachment' => UploadedFile::fake()->create('slip.pdf', 120, 'application/pdf'),
        ]);

        $entry = DieselEntry::firstOrFail();
        $path = $entry->attachment;
        Storage::disk('public')->assertExists($path);

        $this->asAdmin()
            ->delete(route('admin.hr.trackers.diesel.bulk-destroy'), ['ids' => [$entry->id]])
            ->assertRedirect();

        $this->assertSame(0, DieselEntry::count());
        // A bulk delete that skipped this would quietly fill the disk.
        Storage::disk('public')->assertMissing($path);
    }

    #[Test]
    public function ticked_visitor_entries_are_deleted_together(): void
    {
        $keep = VisitorLog::create([
            'business_id' => $this->business->id, 'visit_date' => now()->toDateString(),
            'visitor_name' => 'Keep Me', 'availability_status' => 'available', 'outcome' => 'selected',
        ]);
        $drop = VisitorLog::create([
            'business_id' => $this->business->id, 'visit_date' => now()->toDateString(),
            'visitor_name' => 'Drop Me', 'availability_status' => 'available', 'outcome' => 'selected',
        ]);

        $this->asAdmin()
            ->delete(route('admin.hr.trackers.visitors.bulk-destroy'), ['ids' => [$drop->id]])
            ->assertRedirect();

        $this->assertSame([$keep->id], VisitorLog::pluck('id')->all());
    }

    private function makeBreak(string $out, ?string $in, ?string $date = null, ?int $typeId = null): BreakSheet
    {
        return BreakSheet::create([
            'business_id' => $this->business->id,
            'employee_id' => $this->employee->id,
            'break_date' => $date ?? now()->toDateString(),
            'out_time' => $out,
            'in_time' => $in,
            'duration_minutes' => BreakSheet::minutesBetween($out, $in),
            'break_type_id' => $typeId,
        ]);
    }
}
