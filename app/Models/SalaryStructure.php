<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryStructure extends Model
{
    use BelongsToBusiness;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'business_id',
        'employee_id', 'effective_from', 'effective_to',
        'basic', 'hra', 'conveyance', 'medical', 'special', 'other_allowance',
        'gross_monthly', 'ctc_annual',
        'pf_percent', 'esi_percent', 'professional_tax', 'monthly_tds',
        'is_current', 'notes', 'created_by',
        'status', 'submitted_by', 'submitted_at',
        'reviewed_by', 'reviewed_at', 'review_notes',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_to' => 'date',
            'is_current' => 'boolean',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'basic' => 'decimal:2',
            'hra' => 'decimal:2',
            'conveyance' => 'decimal:2',
            'medical' => 'decimal:2',
            'special' => 'decimal:2',
            'other_allowance' => 'decimal:2',
            'gross_monthly' => 'decimal:2',
            'ctc_annual' => 'decimal:2',
            'pf_percent' => 'decimal:2',
            'esi_percent' => 'decimal:2',
            'professional_tax' => 'decimal:2',
            'monthly_tds' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'submitted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }
}
