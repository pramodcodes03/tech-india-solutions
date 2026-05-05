<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\Appraisal;
use App\Models\Employee;
use App\Services\AppraisalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Simple per-employee increment flow.
 *
 * This is the HR-friendly, no-workflow path for quickly recording a raise
 * against a single employee. It still creates an Appraisal row under the
 * hood (so it shows up in history/PDFs/reports) but skips cycles, self-review,
 * goals, manager review, and the multi-stage workflow.
 */
class IncrementController extends Controller
{
    public function __construct(protected AppraisalService $service) {}

    public function create(Employee $employee)
    {
        abort_unless(Auth::guard('admin')->user()->can('appraisals.create'), 403);

        $currentCtc = $employee->currentSalary?->ctc_annual;

        return view('admin.hr.increments.create', compact('employee', 'currentCtc'));
    }

    public function store(Request $request, Employee $employee)
    {
        abort_unless(Auth::guard('admin')->user()->can('appraisals.create'), 403);

        $data = $request->validate([
            'review_date' => ['required', 'date'],
            'performance_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'hike_percent' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'new_ctc_annual' => ['nullable', 'numeric', 'min:0'],
            'effective_from' => ['nullable', 'date'],
            'strengths' => ['nullable', 'string'],
            'improvement_areas' => ['nullable', 'string'],
            'manager_comments' => ['nullable', 'string'],
        ]);

        // Period: 12 months ending at review_date (sensible default).
        $periodEnd = $data['review_date'];
        $periodStart = \Carbon\Carbon::parse($periodEnd)->subYear()->toDateString();

        // Derive hike / new CTC if only one was supplied.
        $currentCtc = $employee->currentSalary?->ctc_annual;
        if (! empty($data['hike_percent']) && empty($data['new_ctc_annual']) && $currentCtc) {
            $data['new_ctc_annual'] = round($currentCtc * (1 + $data['hike_percent'] / 100), 2);
        } elseif (empty($data['hike_percent']) && ! empty($data['new_ctc_annual']) && $currentCtc) {
            $data['hike_percent'] = round((($data['new_ctc_annual'] - $currentCtc) / $currentCtc) * 100, 2);
        }

        $snap = $this->service->snapshot(
            $employee,
            $periodStart,
            $periodEnd,
            (float) $data['performance_score']
        );

        $appraisal = Appraisal::create(array_merge([
            'appraisal_code' => $this->service->generateCode(),
            'employee_id' => $employee->id,
            'cycle' => 'Increment '.\Carbon\Carbon::parse($data['review_date'])->format('M Y'),
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'performance_score' => $data['performance_score'],
            'recommended_hike_percent' => $data['hike_percent'] ?? null,
            'new_ctc_annual' => $data['new_ctc_annual'] ?? null,
            'effective_from' => $data['effective_from'] ?? null,
            'strengths' => $data['strengths'] ?? null,
            'improvement_areas' => $data['improvement_areas'] ?? null,
            'manager_comments' => $data['manager_comments'] ?? null,
            'status' => 'finalized',
            'conducted_by' => Auth::guard('admin')->id(),
        ], $snap));

        \App\Notifications\NotificationDispatcher::fire(
            'appraisal.recorded',
            $appraisal->setRelation('employee', $employee),
        );

        return redirect()->route('admin.hr.employees.show', $employee)
            ->with('success', 'Increment recorded for '.$employee->full_name.'.');
    }
}
