<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseBudget extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'expense_category_id', 'employee_id', 'period_type',
        'period_start', 'period_end', 'amount', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    /** The employee this budget is sanctioned to (null = category-wide). */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /** True when this budget is earmarked for a specific employee. */
    public function getIsEmployeeBudgetAttribute(): bool
    {
        return ! empty($this->employee_id);
    }

    /** Expenses the employee submitted against this budget via "Utilize Budget". */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'expense_budget_id');
    }

    /** Extra money added to this budget mid-period. */
    public function topups(): HasMany
    {
        return $this->hasMany(ExpenseBudgetTopup::class, 'expense_budget_id');
    }

    /**
     * Sum of all top-ups. Prefers the eager-loaded relation; falls back to an
     * unscoped query so a cross-business budget (super admin / employee who
     * works for several companies) still totals correctly.
     */
    public function getTopupsTotalAttribute(): float
    {
        if ($this->relationLoaded('topups')) {
            return (float) $this->topups->sum('amount');
        }

        return (float) ExpenseBudgetTopup::withoutGlobalScopes()
            ->where('expense_budget_id', $this->id)
            ->sum('amount');
    }

    /** Spendable total = sanctioned base + every top-up. */
    public function getTotalAmountAttribute(): float
    {
        return (float) $this->amount + $this->topups_total;
    }

    /**
     * Where the original sanctioned amount sits on a 0-100 bar of the total —
     * drives the "original budget ended here" marker on the progress bar.
     */
    public function getBaseSharePercentAttribute(): float
    {
        $total = $this->total_amount;

        return $total > 0 ? round((float) $this->amount / $total * 100, 2) : 100;
    }

    /**
     * Amount spent against this budget.
     *
     *  - Employee-sanctioned budget → sum of expenses the employee submitted
     *    against this budget ("Utilize Budget"). This is what the dashboard's
     *    Utilised / Remaining / % bar reflect, updated in real time.
     *  - Category-wide budget → all expenses + approved claims in the category
     *    within the period (legacy behaviour).
     */
    public function getUtilizedAttribute(): float
    {
        if ($this->is_employee_budget) {
            // expense_budget_id uniquely identifies this budget's spends, so we
            // drop the tenant scope — otherwise a cross-business budget viewed
            // from another business would compute 0 utilised.
            return (float) Expense::withoutGlobalScopes()
                ->where('expense_budget_id', $this->id)
                ->where('status', '!=', Expense::STATUS_CANCELLED)
                ->sum('amount');
        }

        $expenses = Expense::where('expense_category_id', $this->expense_category_id)
            ->whereBetween('expense_date', [$this->period_start, $this->period_end])
            ->sum('amount');

        $claims = ReimbursementClaim::where('expense_category_id', $this->expense_category_id)
            ->whereIn('status', ['approved', 'disbursed'])
            ->whereBetween('claim_date', [$this->period_start, $this->period_end])
            ->sum('amount');

        return (float) ($expenses + $claims);
    }

    /** Remaining is measured against the topped-up total, not the base. */
    public function getRemainingAttribute(): float
    {
        return $this->total_amount - $this->utilized;
    }

    public function getUtilizationPercentAttribute(): float
    {
        $total = $this->total_amount;

        return $total > 0 ? round($this->utilized / $total * 100, 1) : 0;
    }
}
