<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReimbursementClaim extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'claim_code', 'employee_id', 'expense_category_id',
        'title', 'purpose', 'amount', 'claim_date', 'bill_path',
        'status', 'reviewed_by', 'reviewed_at', 'review_remarks',
        'approved_amount', 'disbursed_at', 'payment_reference',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'approved_amount' => 'decimal:2',
            'claim_date' => 'date',
            'reviewed_at' => 'datetime',
            'disbursed_at' => 'datetime',
        ];
    }

    public const STATUSES = [
        'submitted' => 'Submitted',
        'under_review' => 'Under Review',
        'approved' => 'Approved',
        'disbursed' => 'Disbursed',
        'rejected' => 'Rejected',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'reviewed_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ReimbursementClaimLog::class)->latest();
    }
}
