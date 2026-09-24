<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A KPI as assigned under an employee's KRA, with the achieved value and the
 * score derived from it.
 */
class EmployeeKpi extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'employee_kra_id', 'kpi_id',
        'target_value', 'achieved_value', 'weightage', 'score_formula', 'score', 'remarks',
    ];

    protected function casts(): array
    {
        return [
            'target_value' => 'decimal:2',
            'achieved_value' => 'decimal:2',
            'weightage' => 'decimal:2',
            'score' => 'decimal:2',
        ];
    }

    public function employeeKra(): BelongsTo
    {
        return $this->belongsTo(EmployeeKra::class);
    }

    public function kpi(): BelongsTo
    {
        return $this->belongsTo(Kpi::class);
    }

    /**
     * Score this KPI out of 100 from target vs achieved.
     *
     * Capped at 100: over-achievement is recognised in the achieved value and
     * in the manager's rating, but is not allowed to inflate a weighted total
     * past the band boundaries HR configured. Returns null while nothing has
     * been achieved yet, so an un-entered KPI reads as "not scored" rather
     * than as a zero the employee did not earn.
     */
    public static function computeScore(?float $target, ?float $achieved, string $formula): ?float
    {
        if ($achieved === null) {
            return null;
        }

        // No target set: any achievement counts as met, nothing counts as zero.
        if ($target === null || abs($target) < 0.0001) {
            return $achieved > 0 ? 100.0 : 0.0;
        }

        $score = match ($formula) {
            'lower_better' => $achieved <= 0 ? 100.0 : ($target / $achieved) * 100,
            'exact_match' => abs($achieved - $target) < 0.0001 ? 100.0 : 0.0,
            default => ($achieved / $target) * 100,
        };

        return round(max(0.0, min(100.0, $score)), 2);
    }

    /** Recompute and persist this row's score from its own target/achieved. */
    public function recomputeScore(): void
    {
        $this->score = self::computeScore(
            $this->target_value === null ? null : (float) $this->target_value,
            $this->achieved_value === null ? null : (float) $this->achieved_value,
            (string) $this->score_formula,
        );
    }

    public function getAchievementPercentAttribute(): ?float
    {
        if ($this->achieved_value === null || ! $this->target_value) {
            return null;
        }

        return round(((float) $this->achieved_value / (float) $this->target_value) * 100, 1);
    }
}
