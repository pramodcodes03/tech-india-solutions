<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Payslip;
use App\Models\SalaryStructure;
use App\Notifications\NotificationDispatcher;
use App\Services\PayrollService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PayrollController extends Controller
{
    /** Page sizes offered on the payslip list. */
    public const PER_PAGE_OPTIONS = [25, 50, 100, 200];

    public function __construct(protected PayrollService $service) {}

    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('payroll.view'), 403);

        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        // 25 a page meant 17+ pages for a normal month, which makes selecting
        // and deleting in bulk a chore. Default to 100 and let the user pick,
        // from a whitelist so a hand-edited URL cannot ask for the lot.
        $perPage = (int) $request->input('per_page', 100);
        if (! in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = 100;
        }

        $payslips = $this->filtered($request, $month, $year)
            ->with('employee.department')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $totals = Payslip::where('month', $month)->where('year', $year)
            ->selectRaw('SUM(gross_earnings) as gross, SUM(total_deductions) as deductions, SUM(net_pay) as net, COUNT(*) as count')
            ->first();

        $departments = Department::where('status', 'active')->orderBy('name')->get();

        return view('admin.hr.payroll.index', compact('perPage', 'payslips', 'month', 'year', 'totals', 'departments'));
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
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
        ]);

        $month = (int) $data['month'];
        $year = (int) $data['year'];
        $period = sprintf('%s %d', Carbon::create()->month($month)->format('M'), $year);

        // ─── Single employee ───────────────────────────────────────────────
        if (! empty($data['employee_id'])) {
            $employee = Employee::find($data['employee_id']);
            if (! $employee) {
                return back()->withInput()
                    ->with('error', 'Employee not found in the active business.');
            }

            try {
                $payslip = $this->service->generate($employee, $month, $year);
            } catch (\RuntimeException $e) {
                // Expected business-rule failure (e.g. no approved salary structure).
                return back()->withInput()->with('error', $e->getMessage());
            } catch (\Throwable $e) {
                report($e);

                return back()->withInput()->with(
                    'error',
                    "Could not generate payslip for {$employee->employee_code}: {$e->getMessage()}",
                );
            }

            NotificationDispatcher::fire(
                'payslip.generated',
                $payslip->loadMissing('employee'),
                ['period' => $period],
            );

            return redirect()
                ->route('admin.hr.payroll.index', ['month' => $month, 'year' => $year])
                ->with('success', 'Payslip generated.');
        }

        // ─── Bulk ──────────────────────────────────────────────────────────
        try {
            $result = $this->service->generateBulk($month, $year);
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->with('error', "Bulk generation failed: {$e->getMessage()}");
        }

        if (($result['candidates'] ?? 0) === 0) {
            return back()->withInput()->with(
                'warning',
                'No payslips generated — no active employees have an approved current salary structure for this business. '
                .'Check HR → Payroll → Pending Approvals.',
            );
        }

        // Send each generated payslip to its employee.
        $newPayslips = Payslip::with('employee')
            ->where('month', $month)
            ->where('year', $year)
            ->latest()
            ->take($result['success'])
            ->get();
        foreach ($newPayslips as $payslip) {
            NotificationDispatcher::fire(
                'payslip.generated',
                $payslip,
                ['period' => $period],
            );
        }

        // Summary email to HR admin.
        NotificationDispatcher::fire('payroll.completed', null, [
            'period' => $period,
            'employees_count' => $result['success'],
            'errors_count' => count($result['errors']),
        ]);

        $msg = "Generated {$result['success']} of {$result['candidates']} payslips.";
        if (! empty($result['errors'])) {
            $sample = collect($result['errors'])
                ->take(3)
                ->map(fn ($m, $code) => "{$code}: {$m}")
                ->implode('; ');
            $msg .= ' Skipped '.count($result['errors']).' — '.$sample;
            if (count($result['errors']) > 3) {
                $msg .= ' (…)';
            }
        }

        $flashKey = $result['success'] > 0 ? 'success' : 'warning';

        return redirect()
            ->route('admin.hr.payroll.index', ['month' => $month, 'year' => $year])
            ->with($flashKey, $msg);
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

        NotificationDispatcher::fire(
            'payslip.paid',
            $payslip->loadMissing('employee'),
            [
                'period' => sprintf('%s %d', Carbon::create()->month($payslip->month)->format('M'), $payslip->year),
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
        NotificationDispatcher::fire('salary_structure.submitted', $structure);

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
        // Clear the original "Pending approval" bell rows so the badge count
        // doesn't keep pointing at this now-actioned record.
        AdminNotification::markRelatedAsRead($approved, ['salary_structure.submitted']);
        NotificationDispatcher::fire('salary_structure.approved', $approved);

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
        AdminNotification::markRelatedAsRead($rejected, ['salary_structure.submitted']);
        NotificationDispatcher::fire('salary_structure.rejected', $rejected);

        return back()->with('success', 'Salary structure rejected. HR has been notified.');
    }

    /**
     * The payslip query behind the list — shared with bulk delete so "delete
     * everything matching this filter" can never select a different set from
     * the one the user is looking at.
     */
    private function filtered(Request $request, int $month, int $year)
    {
        return Payslip::query()
            ->where('month', $month)
            ->where('year', $year)
            ->when($request->department_id, fn ($q, $id) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $id)))
            ->when($request->search, fn ($q, $s) => $q->whereHas('employee', fn ($e) => $e->where(function ($q) use ($s) {
                $q->where('first_name', 'like', "%{$s}%")
                    ->orWhere('last_name', 'like', "%{$s}%")
                    ->orWhere('employee_code', 'like', "%{$s}%");
            })));
    }

    /**
     * Employee-wise delete: remove one employee's payslip for the month without
     * disturbing the rest of the run.
     */
    public function destroy(Request $request, Payslip $payslip)
    {
        abort_unless(Auth::guard('admin')->user()->can('payroll.delete'), 403);

        $override = $request->boolean('override_paid')
            && Auth::guard('admin')->user()->can('payroll.delete_paid');

        if ($payslip->status === 'paid' && ! $override) {
            return back()->with('error',
                "{$payslip->payslip_code} is already marked Paid. Removing it needs an admin override.");
        }

        $period = $payslip->period_label;
        $result = $this->service->deletePayslips([$payslip], $override);
        $this->logDeletion($result, $period);

        return redirect()
            ->route('admin.hr.payroll.index', ['month' => $payslip->month, 'year' => $payslip->year])
            ->with('success', $this->deletionMessage($result));
    }

    /**
     * Bulk delete: either the ticked rows, or everything matching the current
     * month / department / search filter.
     */
    public function bulkDestroy(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('payroll.delete'), 403);

        $data = $request->validate([
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'between:2020,2100'],
            'select_all' => ['nullable', 'boolean'],
            'ids' => ['nullable', 'array'],
            'ids.*' => ['integer'],
            'override_paid' => ['nullable', 'boolean'],
        ]);

        $override = $request->boolean('override_paid')
            && Auth::guard('admin')->user()->can('payroll.delete_paid');

        $query = $this->filtered($request, (int) $data['month'], (int) $data['year']);

        if (! $request->boolean('select_all')) {
            $ids = $data['ids'] ?? [];
            if (empty($ids)) {
                return back()->with('warning', 'No payslips were selected.');
            }
            $query->whereIn('id', $ids);
        }

        $payslips = $query->get();

        if ($payslips->isEmpty()) {
            return back()->with('warning', 'No payslips matched — nothing was deleted.');
        }

        $period = Carbon::createFromDate((int) $data['year'], (int) $data['month'], 1)->format('F Y');
        $result = $this->service->deletePayslips($payslips, $override);
        $this->logDeletion($result, $period);

        $level = $result['deleted'] === 0 ? 'warning' : ($result['skipped_paid'] > 0 ? 'warning' : 'success');

        return back()->with($level, $this->deletionMessage($result));
    }

    /**
     * One audit entry per action carrying the user, the count and the payslip
     * codes — enough to reconstruct what was removed without a row per slip.
     */
    private function logDeletion(array $result, string $period): void
    {
        if ($result['deleted'] === 0) {
            return;
        }

        activity('payroll')
            ->causedBy(Auth::guard('admin')->user())
            ->withProperties([
                'period' => $period,
                'deleted' => $result['deleted'],
                'skipped_paid' => $result['skipped_paid'],
                'released_penalties' => $result['released_penalties'],
                'released_adjustments' => $result['released_adjustments'],
                'payslip_codes' => $result['codes'],
            ])
            ->log("Deleted {$result['deleted']} payslip(s) for {$period}");
    }

    /** Plain-English outcome, including what was put back into circulation. */
    private function deletionMessage(array $result): string
    {
        if ($result['deleted'] === 0) {
            return $result['skipped_paid'] > 0
                ? "Nothing deleted — {$result['skipped_paid']} payslip(s) are marked Paid and need an admin override."
                : 'Nothing was deleted.';
        }

        $parts = ["Deleted {$result['deleted']} payslip(s)."];

        if ($result['skipped_paid'] > 0) {
            $parts[] = "{$result['skipped_paid']} already marked Paid were kept — they need an admin override.";
        }
        if ($result['released_penalties'] > 0) {
            $parts[] = "{$result['released_penalties']} penalty deduction(s) returned to pending.";
        }
        if ($result['released_adjustments'] > 0) {
            $parts[] = "{$result['released_adjustments']} payroll adjustment(s) released for the next run.";
        }

        return implode(' ', $parts);
    }
}
