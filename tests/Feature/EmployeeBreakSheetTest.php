<?php

namespace Tests\Feature;

use App\Http\Controllers\Employee\BreakSheetController;
use App\Models\BreakSheet;
use App\Models\Business;
use App\Models\Employee;
use App\Support\Tenancy\CurrentBusiness;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUsers;

/**
 * The employee-facing break sheet. The register existed only under
 * HR → Trackers, so an employee could not see the breaks recorded against
 * them; this is the read-only view of their own rows.
 */
class EmployeeBreakSheetTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    private Business $business;

    private Employee $employee;

    private Employee $colleague;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();

        Carbon::setTestNow(Carbon::parse('2026-09-24 15:00:00'));

        $this->business = Business::create([
            'name' => 'Break Co', 'slug' => 'break-co',
            'currency_code' => 'INR', 'currency_symbol' => '₹',
        ]);
        app(CurrentBusiness::class)->set($this->business);

        $this->employee = $this->makeEmployee('EMP-001', 'Asha');
        $this->colleague = $this->makeEmployee('EMP-002', 'Bala');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function makeEmployee(string $code, string $name): Employee
    {
        return Employee::create([
            'business_id' => $this->business->id, 'employee_code' => $code,
            'email' => strtolower($name).'@break.test', 'first_name' => $name,
            'status' => 'active', 'password' => 'secret', 'joining_date' => '2025-01-01',
        ]);
    }

    /**
     * The table has a natural-key unique index on
     * (business, employee, date, out_time), so each break needs its own
     * out-time rather than sharing one.
     */
    private function break(
        Employee $employee,
        string $date,
        ?string $in = '13:30:00',
        int $minutes = 30,
        string $out = '13:00:00',
    ): BreakSheet {
        return BreakSheet::create([
            'business_id' => $this->business->id,
            'employee_id' => $employee->id,
            'break_date' => $date,
            'out_time' => $out,
            'in_time' => $in,
            'duration_minutes' => $in ? $minutes : null,
        ]);
    }

    // ── Visibility ───────────────────────────────────────────────────────

    #[Test]
    public function an_employee_can_open_their_break_sheet(): void
    {
        $this->break($this->employee, '2026-09-24');

        $this->actingAs($this->employee, 'employee')
            ->get(route('employee.break-sheet.index'))
            ->assertOk()
            ->assertSee('My Break Sheet');
    }

    #[Test]
    public function they_only_see_their_own_breaks(): void
    {
        $this->break($this->employee, '2026-09-24');
        $this->break($this->colleague, '2026-09-24');

        $breaks = $this->actingAs($this->employee, 'employee')
            ->get(route('employee.break-sheet.index'))
            ->assertOk()
            ->viewData('breaks');

        $this->assertCount(1, $breaks);
        $this->assertSame($this->employee->id, $breaks->first()->employee_id);
    }

    #[Test]
    public function the_dashboard_shows_the_break_card(): void
    {
        $this->break($this->employee, '2026-09-24');

        $this->actingAs($this->employee, 'employee')
            ->get(route('employee.dashboard'))
            ->assertOk()
            ->assertSee('Break Sheet')
            ->assertSee(route('employee.break-sheet.index'), false);
    }

    #[Test]
    public function a_guest_is_sent_to_the_login_page(): void
    {
        $this->get(route('employee.break-sheet.index'))->assertRedirect();
    }

    // ── The figures ──────────────────────────────────────────────────────

    #[Test]
    public function the_summary_counts_today_and_the_month_separately(): void
    {
        $this->break($this->employee, '2026-09-24', '13:30:00', 30, '13:00:00');
        $this->break($this->employee, '2026-09-24', '16:15:00', 15, '16:00:00');
        $this->break($this->employee, '2026-09-02', '13:20:00', 20, '13:00:00');

        $summary = BreakSheetController::summaryFor($this->employee->id, 9, 2026);

        $this->assertSame(2, $summary['today_count']);
        $this->assertSame(45, $summary['today_minutes']);
        $this->assertSame(3, $summary['count']);
        $this->assertSame(65, $summary['minutes']);
    }

    #[Test]
    public function a_break_with_no_return_time_is_flagged(): void
    {
        $this->break($this->employee, '2026-09-24', null);

        $summary = BreakSheetController::summaryFor($this->employee->id, 9, 2026);
        $this->assertSame(1, $summary['open']);

        $this->actingAs($this->employee, 'employee')
            ->get(route('employee.break-sheet.index'))
            ->assertOk()
            ->assertSee('Still out');
    }

    #[Test]
    public function another_month_can_be_chosen(): void
    {
        $this->break($this->employee, '2026-08-11');

        $breaks = $this->actingAs($this->employee, 'employee')
            ->get(route('employee.break-sheet.index', ['month' => 8, 'year' => 2026]))
            ->assertOk()
            ->viewData('breaks');

        $this->assertCount(1, $breaks);

        // September has none of August's rows.
        $this->assertCount(0, $this->actingAs($this->employee, 'employee')
            ->get(route('employee.break-sheet.index', ['month' => 9, 'year' => 2026]))
            ->viewData('breaks'));
    }

    #[Test]
    public function durations_read_as_hours_and_minutes(): void
    {
        $this->assertSame('—', BreakSheetController::humanMinutes(0));
        $this->assertSame('—', BreakSheetController::humanMinutes(null));
        $this->assertSame('45m', BreakSheetController::humanMinutes(45));
        $this->assertSame('1h 24m', BreakSheetController::humanMinutes(84));
        $this->assertSame('2h 0m', BreakSheetController::humanMinutes(120));
    }

    #[Test]
    public function the_screen_is_read_only(): void
    {
        // Breaks are recorded by supervisors through the tracker; an employee
        // editing their own would undermine the register.
        $this->assertFalse(Route::has('employee.break-sheet.store'));
        $this->assertFalse(Route::has('employee.break-sheet.destroy'));
    }
}
