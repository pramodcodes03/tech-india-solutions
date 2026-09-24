<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Extra money added to an ExpenseBudget mid-period. The budget's spendable
 * total is always base amount + sum of its top-ups.
 */
class ExpenseBudgetTopup extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'expense_budget_id', 'amount', 'note', 'added_on', 'added_by',
    ];

    protected function casts(): array
    {
        return [
            'added_on' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(ExpenseBudget::class, 'expense_budget_id');
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'added_by');
    }
}
