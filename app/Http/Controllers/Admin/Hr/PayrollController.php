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

        $month = (int) $data['month'];
        $year = (int) $data['year'];
        $period = sprintf('%s %d', \Carbon\Carbon::create()->month($month)->format('M'), $year);

        if (! empty($data['employee_id'])) {
            $employee = Employee::findOrFail($data['employee_id']);
            $payslip = $this->service->generate($employee, $month, $year);

            \App\Notifications\NotificationDispatcher::fire(
                'payslip.generated',
                $payslip->loadMissing('employee'),
                ['period' => $period],
            );

            return redirect()
                ->route('admin.hr.payroll.index', ['month' => $month, 'year' => $year])
                ->with('success', 'Payslip generated.');
        }

        $result = $this->service->generateBulk($month, $year);

        // Send each generated payslip to its employee.
        $newPayslips = \App\Models\Payslip::with('employee')
            ->where('month', $month)
            ->where('year', $year)
            ->latest()
            ->take($result['success'])
            ->get();
        foreach ($newPayslips as $payslip) {
            \App\Notifications\NotificationDispatcher::fire(
                'payslip.generated',
                $payslip,
                ['period' => $period],
            );
        }

        // Summary email to HR admin.
        \App\Notifications\NotificationDispatcher::fire('payroll.completed', null, [
            'period' => $period,
            'employees_count' => $result['success'],
            'errors_count' => count($result['errors']),
        ]);

        $msg = "Generated {$result['success']} payslips.";
        if (! empty($result['errors'])) {
            $msg .= ' Skipped: '.count($result['errors']).' (missing salary structure or other issues).';
        }

        return redirect()
            ->route('admin.hr.payroll.index', ['month' => $month, 'year' => $year])
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
        $payslip->load('employee.department', 'employee.designation', 'employee.business');
        // Tenant identity comes from the payslip's own employee — not the
        // current session — so a super admin who switched business won't get
        // the wrong header on a payslip from another tenant.
        $business = $payslip->employee?->business;
        $pdf = Pdf::loadView('admin.hr.payroll.pdf', compact('payslip', 'business'));

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

        \App\Notifications\NotificationDispatcher::fire(
            'payslip.paid',
            $payslip->loadMissing('employee'),
            [
                'period' => sprintf('%s %d', \Carbon\Carbon::create()->month($payslip->month)->format('M'), $payslip->year),
            ],
        );

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

        $structure = $this->service->saveStructure($employee, $data);
        $structure->setRelation('employee', $employee);

        // Fire approval-pending notification to Admin / Super Admin only.
        // We no longer fire 'salary_structure.changed' here — that event
        // notifies the employee, and the employee shouldn't see CTC changes
        // until approval lands.
        \App\Notifications\NotificationDispatcher::fire('salary_structure.submitted', $structure);

        return redirect()->route('admin.hr.employees.show', $employee)
            ->with('success', 'Salary structure submitted for approval. The previous structure remains in effect until Admin approves.');
    }

    public function previewStructure(Request $request)
    {
        $ctc = (float) $request->input('ctc_annual', 0);
        return response()->json($this->service->buildStructureFromCtc($ctc));
    }

    /**
     * Approval queue — pending salary structures awaiting Admin/Super Admin review.
     */
    public function pendingApprovals()
    {
        $admin = Auth::guard('admin')->user();
        abort_unless(
            $admin->isSuperAdmin() || $admin->hasAnyRole(['Admin', 'Business Admin']),
            403,
            'Only Admin / Super Admin may review salary structure approvals.',
        );

        // Super admin reviews structures across every business — they can't act
        // on pending approvals if they only see the currently-active business's
        // queue. Regular admins stay scoped via the global scope.
        $isSuperAdmin = $admin->isSuperAdmin();
        $query = $isSuperAdmin
            ? SalaryStructure::withoutGlobalScopes()
            : SalaryStructure::query();

        // Cross-business eager loads must bypass BusinessScope on the related
        // tables, otherwise employee/department/designation come back NULL for
        // rows outside the currently-active business.
        $eagerLoad = $isSuperAdmin ? [
            'employee' => fn ($q) => $q->withoutGlobalScopes(),
            'employee.department' => fn ($q) => $q->withoutGlobalScopes(),
            'employee.designation' => fn ($q) => $q->withoutGlobalScopes(),
            'submitter',
            'business',
        ] : ['employee.department', 'employee.designation', 'submitter', 'business'];

        $pending = $query->where('status', SalaryStructure::STATUS_PENDING)
            ->with($eagerLoad)
            ->orderByDesc('submitted_at')
            ->paginate(20);

        return view('admin.hr.payroll.approvals', compact('pending', 'isSuperAdmin'));
    }

    public function approveStructure(Request $request, SalaryStructure $salaryStructure)
    {
        abort_unless(
            Auth::guard('admin')->user()->isSuperAdmin()
                || Auth::guard('admin')->user()->hasAnyRole(['Admin', 'Business Admin']),
            403,
        );

        $data = $request->validate(['notes' => ['nullable', 'string', 'max:1000']]);

        try {
            $approved = $this->service->approveStructure($salaryStructure, $data['notes'] ?? null);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $approved->loadMissing('employee', 'submitter');
        \App\Notifications\NotificationDispatcher::fire('salary_structure.approved', $approved);

        return back()->with('success', "Approved. Structure for {$approved->employee->first_name} is now active.");
    }

    public function rejectStructure(Request $request, SalaryStructure $salaryStructure)
    {
        abort_unless(
            Auth::guard('admin')->user()->isSuperAdmin()
                || Auth::guard('admin')->user()->hasAnyRole(['Admin', 'Business Admin']),
            403,
        );

        $data = $request->validate(['notes' => ['required', 'string', 'min:5', 'max:1000']]);

        try {
            $rejected = $this->service->rejectStructure($salaryStructure, $data['notes']);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $rejected->loadMissing('employee', 'submitter');
        \App\Notifications\NotificationDispatcher::fire('salary_structure.rejected', $rejected);

        return back()->with('success', 'Salary structure rejected. HR has been notified.');
    }
}
