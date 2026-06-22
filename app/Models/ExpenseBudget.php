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

    public function getRemainingAttribute(): float
    {
        return (float) $this->amount - $this->utilized;
    }

    public function getUtilizationPercentAttribute(): float
    {
        return $this->amount > 0 ? round($this->utilized / (float) $this->amount * 100, 1) : 0;
    }
}
