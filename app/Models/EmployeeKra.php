<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A KRA as assigned to one employee for one cycle — the thing that actually
 * gets scored.
 */
class EmployeeKra extends Model
{
    use BelongsToBusiness, LogsActivity;

    /** Where this goal sits in the Employee → Manager → HR → Admin trail. */
    public const STATUSES = [
        'assigned' => 'Assigned',
        'self_submitted' => 'Self-Assessed',
        'manager_reviewed' => 'Manager Reviewed',
        'hr_reviewed' => 'HR Reviewed',
        'finalized' => 'Finalized',
        'sent_back' => 'Sent Back',
    ];

    /** The 1–5 manager scale from the proposal. */
    public const RATINGS = [
        5 => 'Outstanding',
        4 => 'Exceeds Expectations',
        3 => 'Meets Expectations',
        2 => 'Needs Improvement',
        1 => 'Poor',
    ];

    protected $fillable = [
        'business_id', 'performance_cycle_id', 'employee_id', 'kra_id',
        'weightage', 'manager_id', 'status',
        'self_rating', 'self_remarks', 'manager_rating', 'manager_feedback',
        'self_score', 'manager_score', 'hr_score', 'final_score',
        'assigned_by', 'assigned_at',
    ];

    protected function casts(): array
    {
        return [
            'weightage' => 'decimal:2',
            'self_rating' => 'decimal:1',
            'manager_rating' => 'decimal:1',
            'self_score' => 'decimal:2',
            'manager_score' => 'decimal:2',
            'hr_score' => 'decimal:2',
            'final_score' => 'decimal:2',
            'assigned_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $e) => "Assigned KRA was {$e}");
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(PerformanceCycle::class, 'performance_cycle_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function kra(): BelongsTo
    {
        return $this->belongsTo(Kra::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function kpis(): HasMany
    {
        return $this->hasMany(EmployeeKpi::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(PerformanceDocument::class);
    }

    public function scopeForCycle(Builder $query, int $cycleId): Builder
    {
        return $query->where('performance_cycle_id', $cycleId);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    /**
     * Progress against target across this KRA's KPIs, 0–100.
     *
     * Weighted by each KPI's own weightage so the number matches how the KRA
     * will actually be scored — a half-finished KPI worth 60% of the KRA moves
     * the bar more than a finished one worth 5%.
     */
    public function getProgressPercentAttribute(): float
    {
        $kpis = $this->relationLoaded('kpis') ? $this->kpis : $this->kpis()->get();

        if ($kpis->isEmpty()) {
            return 0.0;
        }

        $totalWeight = (float) $kpis->sum('weightage');
        if ($totalWeight <= 0) {
            return round((float) $kpis->avg(fn (EmployeeKpi $k) => $k->score ?? 0), 1);
        }

        $weighted = $kpis->sum(fn (EmployeeKpi $k) => ((float) ($k->score ?? 0)) * (float) $k->weightage);

        return round($weighted / $totalWeight, 1);
    }
}
