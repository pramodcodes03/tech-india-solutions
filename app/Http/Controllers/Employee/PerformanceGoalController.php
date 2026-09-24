<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\EmployeeKra;
use App\Models\PerformanceCycle;
use App\Models\PerformanceDocument;
use App\Models\PerformanceHistory;
use App\Models\PerformanceManagerReview;
use App\Models\PerformanceScore;
use App\Models\PerformanceSelfReview;
use App\Services\Performance\PerformanceScoringService;
use App\Services\Performance\PerformanceWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * The employee's side of Module A: their own goals, their self-assessment, and
 * the manager feedback and score that come back.
 *
 * Gated by ownership rather than by admin permissions — an employee sees their
 * own cycle and nothing else. The one exception is the manager view, where a
 * department head reviews the team reporting to them.
 */
class PerformanceGoalController extends Controller
{
    public function __construct(
        private PerformanceWorkflowService $workflow,
        private PerformanceScoringService $scoring,
    ) {}

    /** My dashboard: goals, progress, pending reviews, trend, rewards. */
    public function index(Request $request)
    {
        $employee = Auth::guard('employee')->user();
        $cycle = $this->resolveCycle($request);

        $goals = $cycle
            ? EmployeeKra::with(['kra', 'kpis.kpi', 'manager', 'documents'])
                ->where('performance_cycle_id', $cycle->id)
                ->where('employee_id', $employee->id)
                ->get()
            : collect();

        // Trend across every cycle this employee has been scored in.
        $history = PerformanceScore::with('cycle', 'band')
            ->where('employee_id', $employee->id)
            ->whereNotNull('final_score')
            ->get()
            ->sortBy(fn (PerformanceScore $s) => $s->cycle?->period_start)
            ->values();

        return view('employee.performance-goals.index', [
            'cycle' => $cycle,
            'cycles' => PerformanceCycle::whereIn('id', EmployeeKra::where('employee_id', $employee->id)
                ->distinct()->pluck('performance_cycle_id'))
                ->orderByDesc('period_start')->get(),
            'goals' => $goals,
            'selfReview' => $cycle ? PerformanceSelfReview::where('performance_cycle_id', $cycle->id)
                ->where('employee_id', $employee->id)->first() : null,
            'managerReview' => $cycle ? PerformanceManagerReview::where('performance_cycle_id', $cycle->id)
                ->where('employee_id', $employee->id)
                ->where('status', 'submitted')->first() : null,
            'score' => $cycle ? PerformanceScore::with('band')->where('performance_cycle_id', $cycle->id)
                ->where('employee_id', $employee->id)->first() : null,
            'history' => $history,
            'timeline' => $cycle ? PerformanceHistory::with('actorAdmin', 'actorEmployee')
                ->where('performance_cycle_id', $cycle->id)
                ->where('employee_id', $employee->id)
                ->latest()->get() : collect(),
        ]);
    }

    /** The self-assessment form for a cycle. */
    public function selfAssessment(Request $request, PerformanceCycle $cycle)
    {
        $employee = Auth::guard('employee')->user();

        $goals = EmployeeKra::with(['kra', 'kpis.kpi', 'documents'])
            ->where('performance_cycle_id', $cycle->id)
            ->where('employee_id', $employee->id)
            ->get();

        abort_if($goals->isEmpty(), 403, 'You have no goals in this cycle.');

        return view('employee.performance-goals.self-assessment', [
            'cycle' => $cycle,
            'goals' => $goals,
            'review' => PerformanceSelfReview::firstOrNew([
                'performance_cycle_id' => $cycle->id,
                'employee_id' => $employee->id,
            ]),
        ]);
    }

    /** Save as draft, or submit and lock. */
    public function storeSelfAssessment(Request $request, PerformanceCycle $cycle)
    {
        $employee = Auth::guard('employee')->user();

        abort_unless(
            EmployeeKra::where('performance_cycle_id', $cycle->id)->where('employee_id', $employee->id)->exists(),
            403,
        );

        $data = $request->validate([
            'achievements' => ['nullable', 'string', 'max:5000'],
            'challenges' => ['nullable', 'string', 'max:5000'],
            'learnings' => ['nullable', 'string', 'max:5000'],
            'future_goals' => ['nullable', 'string', 'max:5000'],
            'comments' => ['nullable', 'string', 'max:5000'],
            'action' => ['required', 'in:draft,submit'],
            'kra' => ['nullable', 'array'],
            'kra.*.rating' => ['nullable', 'numeric', 'min:1', 'max:5'],
            'kra.*.remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        // Submitting needs something written down — an empty submission tells
        // the manager nothing and cannot be undone by the employee.
        if ($data['action'] === 'submit' && blank($data['achievements'] ?? null)) {
            return back()->withInput()->with('error', 'Write at least your achievements before submitting.');
        }

        $payload = collect($data)->only(['achievements', 'challenges', 'learnings', 'future_goals', 'comments'])->all();

        try {
            $this->saveKraSelfRatings($cycle, $employee->id, $data['kra'] ?? []);

            if ($data['action'] === 'draft') {
                $this->workflow->saveSelfReviewDraft($cycle, $employee, $payload);

                return back()->with('success', 'Draft saved. Come back and submit when you are ready.');
            }

            $this->workflow->submitSelfReview($cycle, $employee, $payload);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('employee.performance-goals.index', ['cycle' => $cycle->id])
            ->with('success', 'Self-assessment submitted. It is now with your manager.');
    }

    /** Attach evidence to one of my KRAs. */
    public function uploadEvidence(Request $request, EmployeeKra $goal)
    {
        $employee = Auth::guard('employee')->user();
        abort_unless($goal->employee_id === $employee->id, 403);

        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:5120'],
        ], [
            'file.mimes' => 'Evidence must be a PDF, PNG or JPG.',
            'file.max' => 'Evidence may not be larger than 5 MB.',
        ]);

        $file = $request->file('file');

        PerformanceDocument::create([
            'business_id' => $goal->business_id,
            'performance_cycle_id' => $goal->performance_cycle_id,
            'employee_id' => $employee->id,
            'employee_kra_id' => $goal->id,
            'file_path' => $file->store('performance/evidence', 'public'),
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size_bytes' => $file->getSize(),
            'uploaded_by_employee_id' => $employee->id,
        ]);

        return back()->with('success', 'Evidence attached.');
    }

    public function deleteEvidence(PerformanceDocument $document)
    {
        $employee = Auth::guard('employee')->user();
        abort_unless($document->employee_id === $employee->id, 403);

        // Once the assessment is with the manager the evidence is part of what
        // they are reviewing, so it can no longer be pulled.
        $goal = $document->employeeKra;
        if ($goal && ! in_array($goal->status, ['assigned', 'sent_back'], true)) {
            return back()->with('error', 'This assessment has been submitted — its evidence can no longer be removed.');
        }

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return back()->with('success', 'Evidence removed.');
    }

    public function downloadEvidence(PerformanceDocument $document)
    {
        $employee = Auth::guard('employee')->user();

        // Own evidence, or evidence belonging to someone reporting to me.
        $isOwn = $document->employee_id === $employee->id;
        $isMyTeam = $document->employeeKra?->manager_id === $employee->id;
        abort_unless($isOwn || $isMyTeam, 403);
        abort_unless(Storage::disk('public')->exists($document->file_path), 404);

        return Storage::disk('public')->response($document->file_path, $document->original_name);
    }

    /** Save the employee's own 1–5 rating and remarks against each KRA. */
    private function saveKraSelfRatings(PerformanceCycle $cycle, int $employeeId, array $ratings): void
    {
        foreach ($ratings as $goalId => $values) {
            $goal = EmployeeKra::where('performance_cycle_id', $cycle->id)
                ->where('employee_id', $employeeId)
                ->find($goalId);

            if (! $goal || ! in_array($goal->status, ['assigned', 'sent_back'], true)) {
                continue;
            }

            $goal->update([
                'self_rating' => $values['rating'] ?? $goal->self_rating,
                'self_remarks' => $values['remarks'] ?? $goal->self_remarks,
            ]);
        }
    }

    private function resolveCycle(Request $request): ?PerformanceCycle
    {
        $employee = Auth::guard('employee')->user();

        $mine = EmployeeKra::where('employee_id', $employee->id)->distinct()->pluck('performance_cycle_id');

        if ($request->filled('cycle')) {
            return PerformanceCycle::whereIn('id', $mine)->find($request->input('cycle'));
        }

        return PerformanceCycle::whereIn('id', $mine)->open()->orderByDesc('period_start')->first()
            ?? PerformanceCycle::whereIn('id', $mine)->orderByDesc('period_start')->first();
    }
}
