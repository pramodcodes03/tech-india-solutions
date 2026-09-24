<?php

namespace App\Http\Controllers\Admin\Hr\Performance;

use App\Http\Controllers\Controller;
use App\Models\Appraisal;
use App\Models\PerformanceBand;
use App\Models\PerformanceCycle;
use App\Models\PerformanceScore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Reward recommendations: what the system suggests from the band, and HR's
 * decision to accept, override or reject it.
 *
 * Accepting a reward writes an appraisal record, which is what feeds the
 * existing increment history rather than starting a parallel one.
 */
class RewardController extends Controller
{
    private function gate(string $action): void
    {
        abort_unless(Auth::guard('admin')->user()->can("performance_rewards.{$action}"), 403);
    }

    public function index(Request $request)
    {
        $this->gate('view');

        $cycle = $this->resolveCycle($request);

        if (! $cycle) {
            return view('admin.hr.performance.rewards.index', [
                'cycle' => null, 'cycles' => collect(), 'scores' => collect(), 'summary' => [],
            ]);
        }

        $scores = PerformanceScore::with(['employee.department', 'band', 'appraisal'])
            ->where('performance_cycle_id', $cycle->id)
            ->when($request->recommendation, fn ($q, $r) => $q->where('recommendation', $r))
            ->when($request->status, fn ($q, $s) => $q->where('recommendation_status', $s))
            ->orderByDesc('final_score')
            ->get();

        $summary = $scores->groupBy(fn (PerformanceScore $s) => $s->effective_recommendation)
            ->map->count();

        return view('admin.hr.performance.rewards.index', [
            'cycle' => $cycle,
            'cycles' => PerformanceCycle::orderByDesc('period_start')->get(),
            'scores' => $scores,
            'summary' => $summary,
        ]);
    }

    /**
     * HR's decision on one recommendation.
     *
     * Accepting creates the appraisal record so the reward reaches the existing
     * increment history; overriding does the same with the chosen reward.
     */
    public function decide(Request $request, PerformanceScore $score)
    {
        $this->gate('manage');

        $data = $request->validate([
            'decision' => ['required', 'in:accepted,overridden,rejected'],
            'recommendation_override' => [
                'nullable',
                Rule::requiredIf(fn () => $request->input('decision') === 'overridden'),
                Rule::in(array_keys(PerformanceBand::RECOMMENDATIONS)),
            ],
            'recommendation_notes' => ['nullable', 'string', 'max:500'],
            'hike_percent' => ['nullable', 'numeric', 'min:0', 'max:200'],
        ], [
            'recommendation_override.required' => 'Choose the reward you are overriding to.',
        ]);

        DB::transaction(function () use ($score, $data, $request) {
            $score->update([
                'recommendation_status' => $data['decision'],
                'recommendation_override' => $data['decision'] === 'overridden' ? $data['recommendation_override'] : null,
                'recommendation_notes' => $data['recommendation_notes'] ?? null,
            ]);

            // A rejected recommendation leaves no appraisal behind; if one was
            // already written by an earlier accept, unlink it.
            if ($data['decision'] === 'rejected') {
                $score->update(['appraisal_id' => null]);

                return;
            }

            $this->writeAppraisal($score, $request->input('hike_percent'));
        });

        $score->refresh();

        return back()->with('success', match ($data['decision']) {
            'accepted' => "Accepted: {$score->recommendation_label} for {$score->employee?->full_name}.",
            'overridden' => "Overridden to {$score->recommendation_label}.",
            default => 'Recommendation rejected — no appraisal record was created.',
        });
    }

    /**
     * Push the accepted reward into the existing appraisal / increment record,
     * so Module A feeds the history HR already uses rather than duplicating it.
     */
    private function writeAppraisal(PerformanceScore $score, mixed $hikePercent): void
    {
        $score->loadMissing('cycle', 'employee');
        $cycle = $score->cycle;

        if (! $cycle || ! $score->employee) {
            return;
        }

        $appraisal = Appraisal::updateOrCreate(
            ['id' => $score->appraisal_id],
            [
                'business_id' => $score->business_id,
                'appraisal_code' => $score->appraisal_id
                    ? Appraisal::find($score->appraisal_id)?->appraisal_code
                    : 'APR-'.now()->format('Ym').'-'.str_pad((string) $score->employee_id, 4, '0', STR_PAD_LEFT),
                'employee_id' => $score->employee_id,
                'cycle' => $cycle->name,
                'period_start' => $cycle->period_start,
                'period_end' => $cycle->period_end,
                'performance_score' => $score->final_score,
                'overall_score' => $score->final_score,
                'rating' => $score->band_name,
                'manager_comments' => $score->recommendation_notes,
                'recommended_hike_percent' => is_numeric($hikePercent) ? (float) $hikePercent : null,
                'status' => 'finalized',
                'conducted_by' => Auth::guard('admin')->id(),
            ],
        );

        $score->update(['appraisal_id' => $appraisal->id]);
    }

    private function resolveCycle(Request $request): ?PerformanceCycle
    {
        if ($request->filled('cycle')) {
            return PerformanceCycle::find($request->input('cycle'));
        }

        return PerformanceCycle::orderByDesc('period_start')->first();
    }
}
