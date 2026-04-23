<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Payslip;
use App\Models\SalaryStructure;
use App\Services\PayrollService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class PayrollController extends Controller
{
    public function __construct(protected PayrollService $service) {}

    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('payroll.view'), 403);

        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        $payslips = Payslip::with('employee.department')
            ->where('month', $month)
            ->where('year', $year)
            ->when($request->department_id, fn ($q, $id) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $id)))
            ->when($request->search, fn ($q, $s) => $q->whereHas('employee', fn ($e) => $e->where(function ($q) use ($s) {
                $q->where('first_name', 'like', "%{$s}%")
                    ->orWhere('last_name', 'like', "%{$s}%")
                    ->orWhere('employee_code', 'like', "%{$s}%");
            })))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $totals = Payslip::where('month', $month)->where('year', $year)
            ->selectRaw('SUM(gross_earnings) as gross, SUM(total_deductions) as deductions, SUM(net_pay) as net, COUNT(*) as count')
            ->first();

        $departments = Department::where('status', 'active')->orderBy('name')->get();

        return view('admin.hr.payroll.index', compact('payslips', 'month', 'year', 'totals', 'departments'));
    }

    public function generateForm()
    {
        abort_unless(Auth::guard('admin')->user()->can('payroll.generate'), 403);

        return view('admin.hr.payroll.generate');
    }

    public function generate(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('payroll.generate'), 403);
        $data = $request->validate([
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'between:2020,2100'],
            'employee_id' => ['nullable', 'exists:employees,id'],
        ]);

        if (! empty($data['employee_id'])) {
            $employee = Employee::findOrFail($data['employee_id']);
            $this->service->generate($employee, $data['month'], $data['year']);

            return redirect()
                ->route('admin.hr.payroll.index', ['month' => $data['month'], 'year' => $data['year']])
                ->with('success', 'Payslip generated.');
        }

        $result = $this->service->generateBulk($data['month'], $data['year']);
        $msg = "Generated {$result['success']} payslips.";
        if (! empty($result['errors'])) {
            $msg .= ' Skipped: '.count($result['errors']).' (missing salary structure or other issues).';
        }

        return redirect()
            ->route('admin.hr.payroll.index', ['month' => $data['month'], 'year' => $data['year']])
            ->with('success', $msg);
    }

    public function show(Payslip $payslip)
    {
        abort_unless(Auth::guard('admin')->user()->can('payroll.view'), 403);
        $payslip->load('employee.department', 'employee.designation', 'employee.currentSalary', 'penalties.penaltyType');

        return view('admin.hr.payroll.show', compact('payslip'));
    }

    public function pdf(Payslip $payslip)
    {
        abort_unless(Auth::guard('admin')->user()->can('payroll.view'), 403);
        $payslip->load('employee.department', 'employee.designation');
        $pdf = Pdf::loadView('admin.hr.payroll.pdf', compact('payslip'));

        // Stream inline so the browser opens the PDF in-tab instead of downloading.
        return $pdf->stream("payslip-{$payslip->payslip_code}.pdf");
    }

    public function markPaid(Request $request, Payslip $payslip)
    {
        abort_unless(Auth::guard('admin')->user()->can('payroll.approve'), 403);
        $data = $request->validate([
            'paid_on' => ['required', 'date'],
            'payment_reference' => ['nullable', 'string', 'max:100'],
        ]);
        $payslip->update(array_merge($data, ['status' => 'paid']));

        return back()->with('success', 'Payslip marked as paid.');
    }

    // ── Salary structure ─────────────────────────────────────────────────
    public function salaryForm(Employee $employee)
    {
        abort_unless(Auth::guard('admin')->user()->can('salary_structures.create'), 403);
        $current = $employee->salaryStructures()->where('is_current', true)->first();

        return view('admin.hr.payroll.salary', compact('employee', 'current'));
    }

    public function salaryStore(Request $request, Employee $employee)
    {
        abort_unless(Auth::guard('admin')->user()->can('salary_structures.create'), 403);
        $data = $request->validate([
            'effective_from' => ['required', 'date'],
            'ctc_annual' => ['required', 'numeric', 'min:0'],
            'basic' => ['required', 'numeric', 'min:0'],
            'hra' => ['required', 'numeric', 'min:0'],
            'conveyance' => ['required', 'numeric', 'min:0'],
            'medical' => ['required', 'numeric', 'min:0'],
            'special' => ['required', 'numeric', 'min:0'],
            'other_allowance' => ['nullable', 'numeric', 'min:0'],
            'pf_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'esi_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'professional_tax' => ['required', 'numeric', 'min:0'],
            'monthly_tds' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);
        $data['gross_monthly'] = $data['basic'] + $data['hra'] + $data['conveyance'] + $data['medical'] + $data['special'] + ($data['other_allowance'] ?? 0);

        $this->service->saveStructure($employee, $data);

        return redirect()->route('admin.hr.employees.show', $employee)->with('success', 'Salary structure saved.');
    }

    public function previewStructure(Request $request)
    {
        $ctc = (float) $request->input('ctc_annual', 0);
        return response()->json($this->service->buildStructureFromCtc($ctc));
    }
}
