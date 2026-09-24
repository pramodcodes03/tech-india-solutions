<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Payslip;
use App\Services\PayrollService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PayslipController extends Controller
{
    public function __construct(private PayrollService $service) {}

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

        $payslip->load('employee.department', 'employee.designation', 'employee.business');
        $business = $payslip->employee?->business;
        $pdf = Pdf::loadView('admin.hr.payroll.pdf', compact('payslip', 'business'));

        // Stream inline so the browser opens the PDF in-tab instead of downloading.
        return $pdf->stream("payslip-{$payslip->payslip_code}.pdf");
    }

    /**
     * Delete payslips from this employee's list — for clearing a payroll run
     * while testing, without a trip to the payroll screen.
     *
     * Gated on the ADMIN guard, never the employee one. A payslip is the
     * employer's wage record as much as the employee's copy — the statutory
     * registers read these same rows — so an employee must not be able to
     * delete their own, and on this screen they never see the control. Both
     * guards can be authenticated in one browser session, which is what makes
     * the admin check meaningful here rather than merely decorative.
     */
    public function bulkDestroy(Request $request)
    {
        $employee = Auth::guard('employee')->user();

        $admin = Auth::guard('admin')->user();
        abort_unless($admin && $admin->can('payroll.delete'), 403);

        $data = $request->validate([
            'single_id' => ['nullable', 'integer'],
            'ids' => ['nullable', 'array'],
            'ids.*' => ['integer'],
        ]);

        // A row's own Delete button wins over the ticked boxes — see the
        // nested-form note in the view.
        $ids = ! empty($data['single_id'])
            ? [$data['single_id']]
            : ($data['ids'] ?? []);

        if ($ids === []) {
            return back()->withErrors(['ids' => 'Select at least one payslip to delete.']);
        }

        // Scoped through the employee relation: this screen only ever lists
        // their slips, so an id belonging to anyone else is not found here.
        $payslips = $employee->payslips()->whereIn('id', $ids)->get();

        if ($payslips->isEmpty()) {
            return back()->withErrors(['ids' => 'Those payslips could not be found.']);
        }

        // Deleting through the service rather than the model: it releases the
        // penalties and payroll adjustments the slip consumed, which a plain
        // delete would strand as applied against a slip that no longer exists.
        $result = $this->service->deletePayslips(
            $payslips,
            $admin->can('payroll.delete_paid'),
        );

        if ($result['deleted'] > 0) {
            activity('payroll')
                ->causedBy($admin)
                ->withProperties([
                    'employee_id' => $employee->id,
                    'codes' => $result['codes'],
                    'via' => 'employee portal',
                ])
                ->log("Deleted {$result['deleted']} payslip(s) for {$employee->full_name}");
        }

        return back()->with(
            $result['deleted'] === 0 ? 'warning' : 'success',
            $this->deletionMessage($result),
        );
    }

    /** @param  array{deleted:int, skipped_paid:int}  $result */
    private function deletionMessage(array $result): string
    {
        if ($result['deleted'] === 0) {
            return $result['skipped_paid'] > 0
                ? 'Nothing deleted — a payslip already marked paid cannot be removed without the paid-payslip permission.'
                : 'Nothing was deleted.';
        }

        $message = $result['deleted'] === 1
            ? 'Payslip deleted.'
            : "{$result['deleted']} payslips deleted.";

        if ($result['skipped_paid'] > 0) {
            $message .= " {$result['skipped_paid']} already marked paid "
                .($result['skipped_paid'] === 1 ? 'was' : 'were').' left in place.';
        }

        return $message;
    }
}
