<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Payslip;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class PayslipController extends Controller
{
    public function index()
    {
        $employee = Auth::guard('employee')->user();
        $payslips = $employee->payslips()->whereIn('status', ['generated', 'paid'])->latest()->paginate(12);

        return view('employee.payslips.index', compact('payslips'));
    }

    public function show(Payslip $payslip)
    {
        $employee = Auth::guard('employee')->user();
        abort_unless($payslip->employee_id === $employee->id, 403);
        $payslip->load('employee.department', 'employee.designation', 'penalties.penaltyType');

        return view('employee.payslips.show', compact('payslip'));
    }

    public function pdf(Payslip $payslip)
    {
        $employee = Auth::guard('employee')->user();
        abort_unless($payslip->employee_id === $employee->id, 403);

        $payslip->load('employee.department', 'employee.designation');
        $pdf = Pdf::loadView('admin.hr.payroll.pdf', compact('payslip'));

        // Stream inline so the browser opens the PDF in-tab instead of downloading.
        return $pdf->stream("payslip-{$payslip->payslip_code}.pdf");
    }
}
