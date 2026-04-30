<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Appraisal extends Model
{
    use BelongsToBusiness, LogsActivity;

    public const STATUSES = [
        'finalized' => 'Finalized',
    ];

    protected $fillable = [
        'business_id',
        'appraisal_code', 'employee_id', 'cycle',
        'period_start', 'period_end',
        'performance_score',
        'attendance_score', 'leave_score', 'penalty_score', 'warning_score',
        'overall_score', 'rating',
        'present_days', 'absent_days', 'leave_days',
        'penalty_count', 'penalty_total', 'warning_count',
        'strengths', 'improvement_areas', 'manager_comments', 'employee_comments',
        'recommended_hike_percent', 'new_ctc_annual', 'effective_from',
        'status', 'conducted_by',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'effective_from' => 'date',
            'performance_score' => 'decimal:2',
            'attendance_score' => 'decimal:2',
            'leave_score' => 'decimal:2',
            'penalty_score' => 'decimal:2',
            'warning_score' => 'decimal:2',
            'overall_score' => 'decimal:2',
            'leave_days' => 'decimal:2',
            'penalty_total' => 'decimal:2',
            'recommended_hike_percent' => 'decimal:2',
            'new_ctc_annual' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $e) => "Appraisal was {$e}");
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function conductor(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'conducted_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function getCurrentCtcAttribute(): ?float
    {
        return optional($this->employee?->currentSalary)->ctc_annual;
    }

    public function getHikeAmountAttribute(): ?float
    {
        if (! $this->new_ctc_annual || ! $this->current_ctc) {
            return null;
        }

        return (float) ($this->new_ctc_annual - $this->current_ctc);
    }
}
