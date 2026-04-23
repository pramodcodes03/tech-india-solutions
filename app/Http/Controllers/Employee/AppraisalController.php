<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Appraisal;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class AppraisalController extends Controller
{
    public function index()
    {
        $employee = Auth::guard('employee')->user();

        $appraisals = $employee->appraisals()
            ->latest('effective_from')
            ->latest('period_end')
            ->paginate(10);

        return view('employee.appraisals.index', compact('appraisals', 'employee'));
    }

    public function show(Appraisal $appraisal)
    {
        $employee = Auth::guard('employee')->user();
        abort_unless($appraisal->employee_id === $employee->id, 403);

        $appraisal->load('employee.department', 'employee.designation');

        return view('employee.appraisals.show', compact('appraisal'));
    }

    public function pdf(Appraisal $appraisal)
    {
        $employee = Auth::guard('employee')->user();
        abort_unless($appraisal->employee_id === $employee->id, 403);

        $appraisal->load('employee.department', 'employee.designation', 'employee.currentSalary');
        $pdf = Pdf::loadView('admin.hr.appraisals.pdf', compact('appraisal'));

        return $pdf->stream("appraisal-{$appraisal->appraisal_code}.pdf");
    }
}
