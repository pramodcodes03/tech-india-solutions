<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\Appraisal;
use App\Models\Employee;
use App\Services\AppraisalService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AppraisalController extends Controller
{
    public function __construct(protected AppraisalService $service) {}

    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('appraisals.view'), 403);

        $appraisals = Appraisal::with('employee.department')
            ->when($request->search, fn ($q, $s) => $q->whereHas('employee', fn ($e) => $e->where(function ($q) use ($s) {
                $q->where('first_name', 'like', "%{$s}%")
                    ->orWhere('last_name', 'like', "%{$s}%")
                    ->orWhere('employee_code', 'like', "%{$s}%");
            })))
            ->when($request->employee_id, fn ($q, $id) => $q->where('employee_id', $id))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $employees = Employee::whereIn('status', ['active', 'probation', 'on_notice'])->orderBy('first_name')->get();

        return view('admin.hr.appraisals.index', compact('appraisals', 'employees'));
    }

    public function show(Appraisal $appraisal)
    {
        abort_unless(Auth::guard('admin')->user()->can('appraisals.view'), 403);
        $appraisal->load(['employee.department', 'employee.designation', 'employee.currentSalary', 'conductor']);

        return view('admin.hr.appraisals.show', compact('appraisal'));
    }

    public function edit(Appraisal $appraisal)
    {
        abort_unless(Auth::guard('admin')->user()->can('appraisals.edit'), 403);
        $appraisal->load('employee.currentSalary');

        return view('admin.hr.appraisals.edit', compact('appraisal'));
    }

    public function update(Request $request, Appraisal $appraisal)
    {
        abort_unless(Auth::guard('admin')->user()->can('appraisals.edit'), 403);
        $data = $request->validate([
            'performance_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'strengths' => ['nullable', 'string'],
            'improvement_areas' => ['nullable', 'string'],
            'manager_comments' => ['nullable', 'string'],
            'recommended_hike_percent' => ['nullable', 'numeric', 'min:0'],
            'new_ctc_annual' => ['nullable', 'numeric', 'min:0'],
            'effective_from' => ['nullable', 'date'],
        ]);

        $snap = $this->service->snapshot(
            $appraisal->employee,
            $appraisal->period_start->toDateString(),
            $appraisal->period_end->toDateString(),
            (float) $data['performance_score'],
        );

        $appraisal->update(array_merge($data, $snap));

        return redirect()->route('admin.hr.appraisals.show', $appraisal)->with('success', 'Appraisal updated.');
    }

    public function destroy(Appraisal $appraisal)
    {
        abort_unless(Auth::guard('admin')->user()->can('appraisals.edit'), 403);
        $employee = $appraisal->employee;
        $appraisal->delete();

        return redirect()->route('admin.hr.employees.show', $employee)
            ->with('success', 'Appraisal removed from history.');
    }

    public function pdf(Appraisal $appraisal)
    {
        abort_unless(Auth::guard('admin')->user()->can('appraisals.view'), 403);
        $appraisal->load('employee.department', 'employee.designation', 'employee.currentSalary');
        $pdf = Pdf::loadView('admin.hr.appraisals.pdf', compact('appraisal'));

        return $pdf->stream("appraisal-{$appraisal->appraisal_code}.pdf");
    }
}
