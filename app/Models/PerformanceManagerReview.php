<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** The manager's assessment for a cycle, including the recommendation flags. */
class PerformanceManagerReview extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'performance_cycle_id', 'employee_id',
        'reviewer_employee_id', 'reviewer_admin_id',
        'overall_rating', 'feedback', 'suggestions', 'comments',
        'recommend_promotion', 'recommend_training', 'training_notes',
        'status', 'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'overall_rating' => 'decimal:1',
            'recommend_promotion' => 'boolean',
            'recommend_training' => 'boolean',
            'submitted_at' => 'datetime',
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

    public function reviewerEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'reviewer_employee_id');
    }

    public function reviewerAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'reviewer_admin_id');
    }

    /** Whoever actually did the review — a department head or an admin. */
    public function getReviewerNameAttribute(): ?string
    {
        if ($this->reviewer_employee_id && $this->reviewerEmployee) {
            return $this->reviewerEmployee->full_name.' (Manager)';
        }

        return $this->reviewerAdmin?->display_name;
    }
}
