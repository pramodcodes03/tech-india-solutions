<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseBudget extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'expense_category_id', 'period_type',
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

    /** Amount spent against this budget's category within its period. */
    public function getUtilizedAttribute(): float
    {
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
