<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One leave type's contribution to a Combined Leave request.
 *
 * @see database/migrations/2026_08_28_120001_create_leave_request_splits_table.php
 */
class LeaveRequestSplit extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'leave_request_id', 'leave_type_id',
        'days', 'paid_days', 'unpaid_days',
    ];

    protected function casts(): array
    {
        return [
            'days' => 'decimal:1',
            'paid_days' => 'decimal:1',
            'unpaid_days' => 'decimal:1',
        ];
    }

    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }
}
