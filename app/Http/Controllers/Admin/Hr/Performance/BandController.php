<?php

namespace App\Http\Controllers\Admin\Hr\Performance;

use App\Http\Controllers\Controller;
use App\Models\PerformanceBand;
use App\Models\PerformanceCycle;
use App\Services\Performance\PerformanceScoringService;
use App\Support\HrSettings;
use App\Support\Tenancy\CurrentBusiness;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/** Bands, the bell curve switch, and applying the curve to a cycle. */
class BandController extends Controller
{
    public function __construct(private PerformanceScoringService $scoring) {}

    private function gate(string $action): void
    {
        abort_unless(Auth::guard('admin')->user()->can("performance.{$action}"), 403);
    }

    public function index()
    {
        $this->gate('view');

        $businessId = app(CurrentBusiness::class)->id();
        $bands = PerformanceBand::ordered();

        return view('admin.hr.performance.bands.index', [
            'bands' => $bands,
            'bellCurveEnabled' => $this->scoring->bellCurveEnabled($businessId),
            // The curve only makes sense if its shares add to 100.
            'curveTotal' => round((float) $bands->sum('bell_curve_percent'), 2),
            'cycles' => PerformanceCycle::orderByDesc('period_start')->limit(12)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->gate('configure');

        $data = $this->validateBand($request);
        PerformanceBand::create($data + ['sort_order' => (int) (PerformanceBand::max('sort_order') ?? 0) + 1]);

        return back()->with('success', 'Band added.');
    }

    public function update(Request $request, PerformanceBand $band)
    {
        $this->gate('configure');

        $band->update($this->validateBand($request, $band));

        return back()->with('success', "Band \"{$band->name}\" updated.");
    }

    public function destroy(PerformanceBand $band)
    {
        $this->gate('configure');

        // A band already stamped on a finalised score is part of that record.
        if ($band->performanceScoresCount() > 0) {
            return back()->with('error', 'This band has been used on finalised scores and cannot be deleted. Edit its range instead.');
        }

        $name = $band->name;
        $band->delete();

        return back()->with('success', "Band \"{$name}\" deleted.");
    }

    /** Turn the optional bell curve on or off for this business. */
    public function toggleBellCurve(Request $request)
    {
        $this->gate('configure');

        $enabled = $request->boolean('enabled');
        HrSettings::setForBusiness('performance_bell_curve_enabled', app(CurrentBusiness::class)->id(), $enabled ? 1 : 0, 'hr');

        return back()->with('success', $enabled
            ? 'Bell curve enabled. Apply it to a cycle once its scores are finalised.'
            : 'Bell curve disabled. Scores keep the band their number earns.');
    }

    /** Force one cycle's finalised scores into the configured distribution. */
    public function applyBellCurve(Request $request)
    {
        $this->gate('configure');

        $data = $request->validate(['performance_cycle_id' => ['required', 'exists:performance_cycles,id']]);
        $cycle = PerformanceCycle::findOrFail($data['performance_cycle_id']);

        if (! $this->scoring->bellCurveEnabled($cycle->business_id)) {
            return back()->with('error', 'Turn the bell curve on before applying it.');
        }

        $result = $this->scoring->applyBellCurve($cycle);

        if ($result['ranked'] === 0) {
            return back()->with('warning', "No finalised scores in \"{$cycle->name}\" to distribute yet.");
        }

        $summary = collect($result['distribution'])
            ->map(fn ($count, $band) => "{$band}: {$count}")
            ->implode(' · ');

        return back()->with('success', "Bell curve applied to {$result['ranked']} employee(s) — {$summary}");
    }

    public function clearBellCurve(Request $request)
    {
        $this->gate('configure');

        $data = $request->validate(['performance_cycle_id' => ['required', 'exists:performance_cycles,id']]);
        $cycle = PerformanceCycle::findOrFail($data['performance_cycle_id']);

        $cleared = $this->scoring->clearBellCurve($cycle);

        return back()->with('success', "Bell curve cleared from {$cleared} score(s) — each keeps the band its number earns.");
    }

    private function validateBand(Request $request, ?PerformanceBand $band = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'min_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'max_score' => ['required', 'numeric', 'min:0', 'max:100', 'gte:min_score'],
            'color' => ['required', 'string', 'max:20'],
            'recommendation' => ['required', Rule::in(array_keys(PerformanceBand::RECOMMENDATIONS))],
            'bell_curve_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ], [
            'max_score.gte' => 'The top of the range must be at or above the bottom.',
        ]);
    }
}
