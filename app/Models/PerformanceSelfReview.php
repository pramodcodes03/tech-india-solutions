<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** The employee's own write-up for a cycle. Draft until submitted, then locked. */
class PerformanceSelfReview extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'performance_cycle_id', 'employee_id',
        'achievements', 'challenges', 'learnings', 'future_goals', 'comments',
        'status', 'submitted_at',
    ];

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime'];
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(PerformanceCycle::class, 'performance_cycle_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** Editable while it is a draft or has been sent back for revision. */
    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'sent_back'], true);
    }
}
