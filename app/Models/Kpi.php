<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use App\Support\Tenancy\CurrentBusiness;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * KPI master — one measurable indicator under a KRA.
 */
class Kpi extends Model
{
    use BelongsToBusiness, LogsActivity;

    public const UNITS = [
        'number' => 'Number',
        'percentage' => 'Percentage',
        'currency' => 'Currency',
        'hours' => 'Hours',
        'rating' => 'Rating',
    ];

    /**
     * How achieved is scored against target.
     *
     *   higher_better  more is better — sales, output, conversions
     *   lower_better   less is better — defects, downtime, escalations
     *   exact_match    hitting the number exactly is the goal
     */
    public const FORMULAS = [
        'higher_better' => 'Higher is better (achieved ÷ target)',
        'lower_better' => 'Lower is better (target ÷ achieved)',
        'exact_match' => 'Exact match (full marks only on target)',
    ];

    protected $fillable = [
        'business_id', 'kra_id', 'code', 'name', 'description',
        'measurement_unit', 'target_value', 'weightage', 'score_formula', 'status',
    ];

    protected function casts(): array
    {
        return [
            'target_value' => 'decimal:2',
            'weightage' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $e) => "KPI was {$e}");
    }

    public function kra(): BelongsTo
    {
        return $this->belongsTo(Kra::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public static function nextCode(?int $businessId = null): string
    {
        $businessId ??= app(CurrentBusiness::class)->id();

        $last = static::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->where('code', 'like', 'KPI-%')
            ->orderByDesc('code')
            ->value('code');

        return sprintf('KPI-%04d', $last ? ((int) substr($last, 4)) + 1 : 1);
    }

    public function getUnitLabelAttribute(): string
    {
        return self::UNITS[$this->measurement_unit] ?? ucfirst((string) $this->measurement_unit);
    }

    /** Format a value the way this KPI's unit reads: ₹1,200 · 85% · 40 hrs. */
    public function formatValue(?float $value): string
    {
        if ($value === null) {
            return '—';
        }

        return match ($this->measurement_unit) {
            'currency' => '₹'.number_format($value, 2),
            'percentage' => rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.').'%',
            'hours' => rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.').' hrs',
            'rating' => number_format($value, 1).' / 5',
            default => rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.'),
        };
    }
}
