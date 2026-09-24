<?php

namespace App\Services\Performance;

use App\Models\EmployeeKpi;
use App\Models\EmployeeKra;
use App\Models\PerformanceBand;
use App\Models\PerformanceCycle;
use App\Models\PerformanceHrReview;
use App\Models\PerformanceManagerReview;
use App\Models\PerformanceScore;
use App\Support\HrSettings;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The scoring engine: KPI → KRA → overall score → band → recommended reward.
 *
 * The whole chain in one place, because every screen that shows a number —
 * the dashboards, the reports, the appraisal record — has to agree on it.
 *
 * How a score is built:
 *
 *   1. Each KPI scores 0–100 from target vs achieved (EmployeeKpi::computeScore).
 *   2. Those roll up into their parent KRA, weighted by KPI weightage.
 *      A KRA with no KPIs falls back to the manager's 1–5 rating × 20.
 *   3. KRAs roll up into the overall score, weighted by KRA weightage —
 *      (Sales × 40%) + (Attendance × 20%) + … from the proposal.
 *   4. HR moderation, when present, replaces the manager's rating in step 2.
 *
 * Weightages are read from the assigned rows, never the master, so a template
 * edited mid-cycle cannot rewrite a review already under way.
 */
class PerformanceScoringService
{
    /** A 1–5 rating expressed out of 100. */
    public const RATING_TO_SCORE = 20;

    // ── KPI level ────────────────────────────────────────────────────────

    /**
     * Recompute and save every KPI score under one assigned KRA, then the
     * KRA's own rolled-up score.
     */
    public function scoreKra(EmployeeKra $employeeKra): float
    {
        $employeeKra->loadMissing('kpis');

        foreach ($employeeKra->kpis as $kpi) {
            $kpi->recomputeScore();
            if ($kpi->isDirty('score')) {
                $kpi->save();
            }
        }

        $score = $this->rollUpKpis($employeeKra->kpis, $employeeKra);

        $employeeKra->self_score = $score;
        $employeeKra->save();

        return $score;
    }

    /**
     * KPI scores → one 0–100 figure for the KRA, weighted by KPI weightage.
     *
     * Un-entered KPIs (achieved still null) are left out of both sides of the
     * average rather than counted as zero — a KRA half-way through a cycle
     * should read as "80% on what has been measured", not "40% overall".
     *
     * @param  Collection<int,EmployeeKpi>  $kpis
     */
    public function rollUpKpis(Collection $kpis, EmployeeKra $employeeKra): float
    {
        $scored = $kpis->filter(fn (EmployeeKpi $k) => $k->score !== null);

        if ($scored->isEmpty()) {
            // No measurable KPIs: fall back to the manager's judgement, which is
            // the only signal available for a qualitative KRA like "Team
            // Collaboration".
            $rating = $employeeKra->manager_rating ?? $employeeKra->self_rating;

            return $rating ? round((float) $rating * self::RATING_TO_SCORE, 2) : 0.0;
        }

        $totalWeight = (float) $scored->sum('weightage');

        if ($totalWeight <= 0) {
            return round((float) $scored->avg(fn (EmployeeKpi $k) => (float) $k->score), 2);
        }

        $weighted = $scored->sum(fn (EmployeeKpi $k) => (float) $k->score * (float) $k->weightage);

        return round($weighted / $totalWeight, 2);
    }

    // ── Cycle level ──────────────────────────────────────────────────────

    /**
     * The overall weighted score for one employee in one cycle.
     *
     * @return array{final:float, self:float, manager:float, kras:Collection, total_weight:float}
     */
    public function computeForEmployee(PerformanceCycle $cycle, int $employeeId): array
    {
        $kras = EmployeeKra::query()
            ->with(['kpis', 'kra'])
            ->where('performance_cycle_id', $cycle->id)
            ->where('employee_id', $employeeId)
            ->get();

        if ($kras->isEmpty()) {
            return ['final' => 0.0, 'self' => 0.0, 'manager' => 0.0, 'kras' => $kras, 'total_weight' => 0.0];
        }

        // HR moderation applies to the whole cycle, so resolve it once.
        $moderated = PerformanceHrReview::where('performance_cycle_id', $cycle->id)
            ->where('employee_id', $employeeId)
            ->value('moderated_rating');

        $totalWeight = (float) $kras->sum('weightage');
        $selfTotal = 0.0;
        $managerTotal = 0.0;
        $finalTotal = 0.0;

        foreach ($kras as $employeeKra) {
            $kpiScore = $this->rollUpKpis($employeeKra->kpis, $employeeKra);

            $selfScore = $employeeKra->self_rating
                ? round((float) $employeeKra->self_rating * self::RATING_TO_SCORE, 2)
                : $kpiScore;

            $managerScore = $employeeKra->manager_rating
                ? round((float) $employeeKra->manager_rating * self::RATING_TO_SCORE, 2)
                : $kpiScore;

            // HR's moderated rating overrides the manager's, per the proposal's
            // "moderate manager ratings where they are out of line".
            $hrScore = $moderated
                ? round((float) $moderated * self::RATING_TO_SCORE, 2)
                : $managerScore;

            // The KRA's own number: measured KPIs where they exist, otherwise
            // the reviewer's rating. Both are already 0–100.
            $finalScore = $employeeKra->kpis->contains(fn (EmployeeKpi $k) => $k->score !== null)
                ? round(($kpiScore + $hrScore) / 2, 2)
                : $hrScore;

            $employeeKra->self_score = $selfScore;
            $employeeKra->manager_score = $managerScore;
            $employeeKra->hr_score = $hrScore;
            $employeeKra->final_score = $finalScore;
            $employeeKra->save();

            $weight = (float) $employeeKra->weightage;
            $selfTotal += $selfScore * $weight;
            $managerTotal += $managerScore * $weight;
            $finalTotal += $finalScore * $weight;
        }

        // Weightages should total 100, but a part-configured employee must still
        // produce a sane number — divide by whatever weight actually exists.
        $divisor = $totalWeight > 0 ? $totalWeight : 1;

        return [
            'final' => round($finalTotal / $divisor, 2),
            'self' => round($selfTotal / $divisor, 2),
            'manager' => round($managerTotal / $divisor, 2),
            'kras' => $kras,
            'total_weight' => $totalWeight,
        ];
    }

    /**
     * Compute, band and save the score for one employee — the action behind
     * "finalise" on the HR review screen.
     */
    public function finalizeEmployee(PerformanceCycle $cycle, int $employeeId, ?int $adminId = null): PerformanceScore
    {
        return DB::transaction(function () use ($cycle, $employeeId, $adminId) {
            $computed = $this->computeForEmployee($cycle, $employeeId);
            $band = PerformanceBand::forScore($computed['final']);

            $existing = PerformanceScore::where('performance_cycle_id', $cycle->id)
                ->where('employee_id', $employeeId)
                ->first();

            $values = [
                'business_id' => $cycle->business_id,
                'self_score' => $computed['self'],
                'manager_score' => $computed['manager'],
                'final_score' => $computed['final'],
                'performance_band_id' => $band?->id,
                'band_name' => $band?->name,
                'computed_at' => now(),
                'finalized_by' => $adminId,
            ];

            // Re-suggest a reward only while HR has not decided. Once they have
            // accepted, overridden or rejected it, their decision stands through
            // any number of re-finalisations.
            //
            // Checked against the row already in the database rather than the
            // model updateOrCreate hands back: a freshly created model carries
            // no column defaults, so reading recommendation_status off it would
            // always miss and the reward would silently stay unset.
            if (! $existing || $existing->recommendation_status === 'suggested') {
                $values['recommendation'] = $this->recommendFor($band, $cycle, $employeeId);
                $values['recommendation_status'] = 'suggested';
            }

            $score = PerformanceScore::updateOrCreate(
                ['performance_cycle_id' => $cycle->id, 'employee_id' => $employeeId],
                $values,
            );

            return $score->refresh();
        });
    }

    // ── Rewards ──────────────────────────────────────────────────────────

    /**
     * The reward a band suggests, upgraded to a promotion when the manager
     * explicitly recommended one and the score supports it.
     */
    public function recommendFor(?PerformanceBand $band, PerformanceCycle $cycle, int $employeeId): string
    {
        $base = $band?->recommendation ?? 'none';

        $managerReview = PerformanceManagerReview::where('performance_cycle_id', $cycle->id)
            ->where('employee_id', $employeeId)
            ->first();

        if (! $managerReview) {
            return $base;
        }

        // A promotion recommendation only carries when the band is one of the
        // top two — otherwise the score contradicts the recommendation and HR
        // should see the band's own suggestion instead.
        if ($managerReview->recommend_promotion && in_array($base, ['promotion', 'increment'], true)) {
            return 'promotion';
        }

        if ($managerReview->recommend_training && $base === 'none') {
            return 'training';
        }

        return $base;
    }

    // ── Bell curve ───────────────────────────────────────────────────────

    public function bellCurveEnabled(?int $businessId): bool
    {
        return HrSettings::boolForBusiness('performance_bell_curve_enabled', $businessId, false);
    }

    /**
     * Force the cycle's scores into the configured distribution.
     *
     * Ranks everyone by final score, then fills each band top-down with its
     * configured share of the headcount. Deliberately stored in
     * `bell_curve_band` rather than overwriting `band_name`: the raw band is
     * what the employee earned, the curve band is what the distribution says,
     * and HR needs to see both to defend a moderation decision.
     *
     * @return array{ranked:int, distribution:array<string,int>}
     */
    public function applyBellCurve(PerformanceCycle $cycle): array
    {
        $bands = PerformanceBand::ordered()->filter(fn (PerformanceBand $b) => $b->bell_curve_percent !== null);

        $scores = PerformanceScore::where('performance_cycle_id', $cycle->id)
            ->whereNotNull('final_score')
            ->orderByDesc('final_score')
            ->orderBy('employee_id')          // stable order for equal scores
            ->get();

        if ($scores->isEmpty() || $bands->isEmpty()) {
            return ['ranked' => 0, 'distribution' => []];
        }

        $total = $scores->count();
        $distribution = [];
        $cursor = 0;

        foreach ($bands->values() as $i => $band) {
            $isLast = $i === $bands->count() - 1;

            // The last band takes the remainder, so rounding never leaves
            // somebody unbanded.
            $take = $isLast
                ? $total - $cursor
                : (int) round($total * ((float) $band->bell_curve_percent / 100));

            $take = max(0, min($take, $total - $cursor));

            for ($n = 0; $n < $take; $n++) {
                $score = $scores[$cursor + $n];
                $score->update([
                    'bell_curve_band' => $band->name,
                    'rank_in_business' => $cursor + $n + 1,
                ]);
            }

            $distribution[$band->name] = $take;
            $cursor += $take;
        }

        return ['ranked' => $cursor, 'distribution' => $distribution];
    }

    /** Clear a previously applied curve, e.g. after re-finalising the cycle. */
    public function clearBellCurve(PerformanceCycle $cycle): int
    {
        return PerformanceScore::where('performance_cycle_id', $cycle->id)
            ->update(['bell_curve_band' => null, 'rank_in_business' => null]);
    }
}
