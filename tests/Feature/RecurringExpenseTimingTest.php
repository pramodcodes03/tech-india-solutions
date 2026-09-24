<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Services\ExpenseService;
use App\Support\Tenancy\CurrentBusiness;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUsers;

/**
 * When the Routine Payment Tracker raises the next instance of a recurring
 * payment.
 *
 * It used to run a cycle ahead, so settling this month's rent immediately
 * spawned next month's unpaid row and the tracker always showed a period that
 * had not started. The next one is now raised on the 1st of its own month.
 */
class RecurringExpenseTimingTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    private Business $business;

    private ExpenseCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();

        $this->business = Business::create([
            'name' => 'Rec Co', 'slug' => 'rec-co',
            'currency_code' => 'INR', 'currency_symbol' => '₹',
        ]);
        app(CurrentBusiness::class)->set($this->business);

        $this->category = ExpenseCategory::create([
            'business_id' => $this->business->id, 'name' => 'Rent',
            'slug' => 'rent', 'status' => 'active',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function template(string $dueDate, string $frequency = Expense::FREQ_MONTHLY, ?int $dueDay = null): Expense
    {
        return Expense::create([
            'business_id' => $this->business->id,
            'expense_code' => 'EXP-TPL-1',
            'expense_category_id' => $this->category->id,
            'title' => 'Monthly Rent',
            'amount' => 25000,
            'expense_date' => $dueDate,
            'due_date' => $dueDate,
            'type' => Expense::TYPE_RECURRING,
            'recurrence_frequency' => $frequency,
            'due_day_of_month' => $dueDay,
            'status' => Expense::STATUS_UNPAID,
        ]);
    }

    private function generate(Expense $template): ?Expense
    {
        return app(ExpenseService::class)->generateNextRecurring($template->fresh());
    }

    // ── Monthly ──────────────────────────────────────────────────────────

    #[Test]
    public function next_months_payment_is_not_raised_during_this_month(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-24 09:00:00'));

        // Due 1 Sep; the next is 1 Oct, which has not started.
        $template = $this->template('2026-09-01', Expense::FREQ_MONTHLY, 1);

        $this->assertNull($this->generate($template));
        $this->assertSame(0, Expense::where('recurring_template_id', $template->id)->count());
    }

    #[Test]
    public function it_is_raised_on_the_first_of_its_own_month(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-01 00:30:00'));

        $template = $this->template('2026-09-01', Expense::FREQ_MONTHLY, 1);

        $instance = $this->generate($template);

        $this->assertNotNull($instance);
        $this->assertSame('2026-10-01', $instance->due_date->toDateString());
    }

    #[Test]
    public function a_mid_month_due_day_is_still_raised_on_the_first(): void
    {
        // Due on the 25th: the row appears on 1 Oct so it is visible all month,
        // not on the 25th itself.
        Carbon::setTestNow(Carbon::parse('2026-10-01 00:30:00'));

        $template = $this->template('2026-09-25', Expense::FREQ_MONTHLY, 25);

        $instance = $this->generate($template);

        $this->assertNotNull($instance);
        $this->assertSame('2026-10-25', $instance->due_date->toDateString());
    }

    #[Test]
    public function paying_this_month_does_not_spawn_next_month(): void
    {
        // The reported behaviour: settle September, and October appeared at once.
        Carbon::setTestNow(Carbon::parse('2026-09-24 09:00:00'));

        $template = $this->template('2026-09-01', Expense::FREQ_MONTHLY, 1);
        $template->update(['status' => Expense::STATUS_PAID]);

        $this->assertNull($this->generate($template));
        $this->assertSame(0, Expense::where('recurring_template_id', $template->id)->count());
    }

    #[Test]
    public function running_twice_in_the_same_month_creates_only_one(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-01 00:30:00'));

        $template = $this->template('2026-09-01', Expense::FREQ_MONTHLY, 1);

        $this->assertNotNull($this->generate($template));
        $this->assertNull($this->generate($template));

        $this->assertSame(1, Expense::where('recurring_template_id', $template->id)->count());
    }

    #[Test]
    public function a_backlog_is_worked_through_one_month_per_run(): void
    {
        // A template left untouched since June catches up a month at a time
        // rather than jumping straight to today.
        Carbon::setTestNow(Carbon::parse('2026-10-05 09:00:00'));

        $template = $this->template('2026-06-01', Expense::FREQ_MONTHLY, 1);

        foreach (['2026-07-01', '2026-08-01', '2026-09-01', '2026-10-01'] as $expected) {
            $instance = $this->generate($template);
            $this->assertNotNull($instance, "expected an instance due {$expected}");
            $this->assertSame($expected, $instance->due_date->toDateString());
        }

        // November has not started, so it stops there.
        $this->assertNull($this->generate($template));
    }

    // ── Other cadences ───────────────────────────────────────────────────

    #[Test]
    public function a_yearly_payment_is_not_raised_months_early(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-24 09:00:00'));

        $template = $this->template('2026-04-01', Expense::FREQ_YEARLY);

        // Next is April 2027 — nowhere near.
        $this->assertNull($this->generate($template));
    }

    #[Test]
    public function a_weekly_payment_keeps_a_short_runway(): void
    {
        // Waiting for the week to begin would leave no notice at all, so weekly
        // is the one cadence that still looks ahead.
        Carbon::setTestNow(Carbon::parse('2026-09-24 09:00:00'));

        $template = $this->template('2026-09-21', Expense::FREQ_WEEKLY);

        $instance = $this->generate($template);

        $this->assertNotNull($instance);
        $this->assertSame('2026-09-28', $instance->due_date->toDateString());
    }

    #[Test]
    public function only_the_original_template_spawns_instances(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-01 00:30:00'));

        $template = $this->template('2026-09-01', Expense::FREQ_MONTHLY, 1);
        $instance = $this->generate($template);

        // An instance must not go on to breed instances of its own.
        $this->assertNull(app(ExpenseService::class)->generateNextRecurring($instance));
    }
}
