<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\Hr\PayrollController;
use App\Models\Admin;
use App\Models\Business;
use App\Models\Employee;
use App\Models\Payslip;
use App\Support\Tenancy\CurrentBusiness;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUsers;

/**
 * The payslip list: how many rows a page holds, and the state the row
 * checkboxes are wired to.
 */
class PayrollTableTest extends TestCase
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
            'name' => 'Pay Co', 'slug' => 'pay-co',
            'currency_code' => 'INR', 'currency_symbol' => '₹',
        ]);
        app(CurrentBusiness::class)->set($this->business);

        $this->admin = Admin::create([
            'name' => 'Priya Admin', 'email' => 'priya@pay.test',
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

    private function payslips(int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $employee = Employee::create([
                'business_id' => $this->business->id, 'employee_code' => 'E'.$i,
                'email' => "e{$i}@pay.test", 'first_name' => 'Emp', 'last_name' => (string) $i,
                'status' => 'active', 'password' => 'secret', 'joining_date' => '2025-01-01',
            ]);

            Payslip::create([
                'business_id' => $this->business->id, 'payslip_code' => 'PS-'.$i,
                'employee_id' => $employee->id, 'month' => 9, 'year' => 2026,
                'period_start' => '2026-09-01', 'period_end' => '2026-09-30',
                'working_days' => 26, 'paid_days' => 26,
                'basic' => 10000, 'gross_earnings' => 10000,
                'total_deductions' => 0, 'net_pay' => 10000, 'status' => 'generated',
            ]);
        }
    }

    private function index(array $params = [])
    {
        return $this->actingAs($this->admin, 'admin')
            ->get(route('admin.hr.payroll.index', array_merge(['month' => 9, 'year' => 2026], $params)));
    }

    // ── Pagination ───────────────────────────────────────────────────────

    #[Test]
    public function a_page_now_holds_a_hundred_rows_by_default(): void
    {
        $this->payslips(120);

        $payslips = $this->index()->assertOk()->viewData('payslips');

        $this->assertSame(100, $payslips->perPage());
        // 120 rows used to be 5 pages at 25; it is 2 now.
        $this->assertSame(2, $payslips->lastPage());
    }

    #[Test]
    public function the_page_size_can_be_changed(): void
    {
        $this->payslips(60);

        foreach ([25, 50, 100, 200] as $size) {
            $payslips = $this->index(['per_page' => $size])->assertOk()->viewData('payslips');
            $this->assertSame($size, $payslips->perPage());
        }
    }

    #[Test]
    public function an_unlisted_page_size_falls_back_to_the_default(): void
    {
        // A hand-edited URL must not be able to pull the whole table.
        $this->payslips(5);

        foreach (['99999', 'all', '0', '-1'] as $bad) {
            $payslips = $this->index(['per_page' => $bad])->assertOk()->viewData('payslips');
            $this->assertSame(100, $payslips->perPage(), "per_page={$bad} should fall back");
        }
    }

    #[Test]
    public function the_page_size_survives_paging_and_filtering(): void
    {
        $this->payslips(60);

        $response = $this->index(['per_page' => 25, 'page' => 2])->assertOk();

        $this->assertSame(25, $response->viewData('payslips')->perPage());
        $this->assertSame(25, $response->viewData('perPage'));
        // withQueryString() keeps per_page on the pagination links.
        $response->assertSee('per_page=25', false);
    }

    // ── Row checkboxes ───────────────────────────────────────────────────

    #[Test]
    public function each_row_checkbox_drives_the_shared_selection(): void
    {
        $this->payslips(3);

        $html = $this->index()->assertOk()->getContent();

        // Same :checked + @change pattern the header uses, rather than the
        // array form of x-model that was not toggling.
        $this->assertStringContainsString('toggleOne(', $html);
        $this->assertStringNotContainsString('x-model.number="selected"', $html);
    }

    #[Test]
    public function the_component_is_given_the_pages_ids_up_front(): void
    {
        $this->payslips(3);

        $ids = Payslip::pluck('id')->map(fn ($id) => (int) $id)->all();
        $html = $this->index()->assertOk()->getContent();

        // Rendered by Blade rather than read back out of the DOM.
        foreach ($ids as $id) {
            $this->assertStringContainsString((string) $id, $html);
        }
        $this->assertStringNotContainsString("querySelectorAll('.row-check')", $html);
    }

    #[Test]
    public function every_row_still_posts_its_id(): void
    {
        $this->payslips(3);

        $html = $this->index()->assertOk()->getContent();

        // The bulk form still reads ids[] on submit, so the fix must not have
        // removed the name attribute along with x-model.
        $this->assertSame(3, substr_count($html, 'name="ids[]"'));
    }

    #[Test]
    public function the_page_size_picker_is_offered(): void
    {
        $this->payslips(3);

        $html = $this->index()->assertOk()->getContent();

        $this->assertStringContainsString('name="per_page"', $html);
        foreach (PayrollController::PER_PAGE_OPTIONS as $n) {
            $this->assertStringContainsString($n.' / page', $html);
        }
    }

    // ── Bulk delete still works ──────────────────────────────────────────

    #[Test]
    public function bulk_delete_still_removes_the_selected_rows(): void
    {
        $this->payslips(3);
        $ids = Payslip::pluck('id')->take(2)->all();

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.hr.payroll.bulk-destroy'), [
                'month' => 9, 'year' => 2026, 'ids' => $ids,
            ])->assertRedirect();

        $this->assertSame(1, Payslip::count());
    }
}
