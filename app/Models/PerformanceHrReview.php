<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * HR's moderation pass: adjust a manager rating that is out of line, verify the
 * score against attendance / penalty / warning data, and finalise.
 */
class PerformanceHrReview extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'performance_cycle_id', 'employee_id', 'reviewer_admin_id',
        'moderated_rating', 'moderation_reason',
        'attendance_percent', 'penalty_count', 'warning_count',
        'comments', 'status', 'finalized_at',
    ];

    protected function casts(): array
    {
        return [
            'moderated_rating' => 'decimal:1',
            'attendance_percent' => 'decimal:2',
            'finalized_at' => 'datetime',
        ];
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(PerformanceCycle::class, 'performance_cycle_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'reviewer_admin_id');
    }
}
