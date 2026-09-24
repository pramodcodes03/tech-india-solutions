<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Score → band mapping, plus the reward that band suggests and its share of the
 * optional bell curve. Entirely HR-editable.
 */
class PerformanceBand extends Model
{
    use BelongsToBusiness, LogsActivity;

    /** Rewards a band can recommend, in the order they escalate. */
    public const RECOMMENDATIONS = [
        'promotion' => 'Promotion',
        'increment' => 'Salary Increment',
        'bonus' => 'Bonus',
        'training' => 'Training',
        'pip' => 'Performance Improvement Plan',
        'none' => 'No action',
    ];

    protected $fillable = [
        'business_id', 'name', 'min_score', 'max_score', 'color',
        'recommendation', 'bell_curve_percent', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'min_score' => 'decimal:2',
            'max_score' => 'decimal:2',
            'bell_curve_percent' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $e) => "Performance band was {$e}");
    }

    /** Ordered best-first, which is how every screen shows them. */
    public static function ordered(): Collection
    {
        return static::orderBy('sort_order')->orderByDesc('min_score')->get();
    }

    /**
     * The band a score falls into.
     *
     * Bands are matched highest-first so a score sitting exactly on a boundary
     * lands in the better band — 95.0 is Outstanding, not Excellent.
     */
    public static function forScore(float $score): ?self
    {
        return static::orderByDesc('min_score')->get()
            ->first(fn (self $band) => $score >= (float) $band->min_score && $score <= (float) $band->max_score)
            ?? static::orderByDesc('min_score')->get()->first(fn (self $band) => $score >= (float) $band->min_score);
    }

    /** How many finalised scores carry this band — deletion guard. */
    public function performanceScoresCount(): int
    {
        return PerformanceScore::where('performance_band_id', $this->id)->count();
    }

    public function getRecommendationLabelAttribute(): string
    {
        return self::RECOMMENDATIONS[$this->recommendation] ?? 'No action';
    }

    public function getRangeLabelAttribute(): string
    {
        return rtrim(rtrim(number_format((float) $this->min_score, 2, '.', ''), '0'), '.')
            .' – '
            .rtrim(rtrim(number_format((float) $this->max_score, 2, '.', ''), '0'), '.');
    }
}
