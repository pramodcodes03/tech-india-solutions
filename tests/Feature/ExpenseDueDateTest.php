<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Business;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Support\Tenancy\CurrentBusiness;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUsers;

class ExpenseDueDateTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    protected Business $business;

    protected Admin $admin;

    protected ExpenseCategory $category;

    protected Expense $expense;

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

        $this->category = ExpenseCategory::create([
            'business_id' => $this->business->id,
            'name' => 'Meta Ads', 'slug' => 'meta-ads', 'is_active' => true,
        ]);

        // An overdue, unpaid one-off payment — the scenario from the bug report.
        $this->expense = Expense::create([
            'business_id' => $this->business->id,
            'expense_code' => 'EXP-2026-0001',
            'expense_category_id' => $this->category->id,
            'type' => Expense::TYPE_ONE_OFF,
            'title' => 'Balance LOW',
            'amount' => 2000,
            'expense_date' => now()->subDays(10)->toDateString(),
            'due_date' => now()->subDays(3)->toDateString(),
            'status' => Expense::STATUS_UNPAID,
            'created_by' => $this->admin->id,
        ]);
    }

    #[Test]
    public function overdue_one_off_due_date_can_be_extended(): void
    {
        $newDue = now()->addDays(7)->toDateString();

        $response = $this->actingAs($this->admin, 'admin')
            ->put(route('admin.expenses.update', $this->expense), [
                'expense_category_id' => $this->category->id,
                'type' => Expense::TYPE_ONE_OFF,
                'title' => 'Balance LOW',
                'amount' => 2000,
                'expense_date' => $this->expense->expense_date->toDateString(),
                'due_date' => $newDue,
                // A stale/cached form still submits the hidden frequency select
                // even for one-off. This must NOT trigger the monthly
                // due_day_of_month requirement (the exact bug from prod).
                'recurrence_frequency' => 'monthly',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.expenses.show', $this->expense));

        $fresh = $this->expense->fresh();
        $this->assertSame($newDue, $fresh->due_date->toDateString());
        $this->assertNull($fresh->recurrence_frequency);
    }

    /**
     * The form has two inputs named due_date (one-off vs non-monthly recurring)
     * plus due_day_of_month, toggled with x-show. x-show only hides — a hidden
     * duplicate still submits and, being last in the DOM, overwrote the date the
     * user picked. The fix binds :disabled on each so hidden fields don't submit.
     */
    #[Test]
    public function edit_form_disables_hidden_duplicate_due_date_inputs(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.expenses.edit', $this->expense));

        $response->assertStatus(200);
        $response->assertSee(':disabled="type !== \'one_off\'"', false);
        $response->assertSee(':disabled="type !== \'recurring\' || frequency === \'monthly\'"', false);
        $response->assertSee(':disabled="type !== \'recurring\' || frequency !== \'monthly\'"', false);
    }
}
