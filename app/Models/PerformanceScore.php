<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * The finalised score for one employee in one cycle, and the reward it led to.
 */
class PerformanceScore extends Model
{
    use BelongsToBusiness, LogsActivity;

    public const RECOMMENDATION_STATUSES = [
        'suggested' => 'Suggested',
        'accepted' => 'Accepted',
        'overridden' => 'Overridden',
        'rejected' => 'Rejected',
    ];

    protected $fillable = [
        'business_id', 'performance_cycle_id', 'employee_id',
        'self_score', 'manager_score', 'final_score',
        'performance_band_id', 'band_name', 'bell_curve_band', 'rank_in_business',
        'recommendation', 'recommendation_status', 'recommendation_override',
        'recommendation_notes', 'appraisal_id', 'computed_at', 'finalized_by',
    ];

    protected function casts(): array
    {
        return [
            'self_score' => 'decimal:2',
            'manager_score' => 'decimal:2',
            'final_score' => 'decimal:2',
            'computed_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $e) => "Performance score was {$e}");
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(PerformanceCycle::class, 'performance_cycle_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function band(): BelongsTo
    {
        return $this->belongsTo(PerformanceBand::class, 'performance_band_id');
    }

    public function appraisal(): BelongsTo
    {
        return $this->belongsTo(Appraisal::class);
    }

    public function finalizer(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'finalized_by');
    }

    /** The reward actually in force — an override wins over the suggestion. */
    public function getEffectiveRecommendationAttribute(): string
    {
        return $this->recommendation_status === 'overridden' && $this->recommendation_override
            ? $this->recommendation_override
            : (string) $this->recommendation;
    }

    public function getRecommendationLabelAttribute(): string
    {
        return PerformanceBand::RECOMMENDATIONS[$this->effective_recommendation] ?? 'No action';
    }
}
