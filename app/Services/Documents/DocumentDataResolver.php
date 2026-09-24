<?php

namespace App\Services\Documents;

use App\Models\Asset;
use App\Models\Attendance;
use App\Models\AttendanceRegularization;
use App\Models\CompOffRequest;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\ExpenseBudget;
use App\Models\GoodsReceipt;
use App\Models\InternalTicket;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\Payment;
use App\Models\Payslip;
use App\Models\PurchaseOrder;
use App\Models\ReimbursementClaim;
use App\Models\ReportTemplate;
use App\Models\Requisition;
use App\Models\SalesOrder;
use App\Models\ServiceTicket;
use App\Models\StockMovement;
use App\Models\Vendor;
use App\Models\Warning;
use App\Services\AttendanceService;
use App\Services\Statutory\StatutoryRegisterService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Turns a document key plus the chosen record / period into the data its Blade
 * template needs.
 *
 * Split out from the controller because several documents share a shape — the
 * fourteen "reports" all render through one table template and differ only in
 * the rows this class builds for them.
 */
class DocumentDataResolver
{
    public function __construct(private StatutoryRegisterService $statutory) {}

    /**
     * Everything the template needs, keyed for `Pdf::loadView`.
     *
     * @param  array{month?:int, year?:int, date?:string, from?:string, to?:string, department_id?:int}  $filters
     */
    public function resolve(string $key, ?Model $record, array $filters = []): array
    {
        return match ($key) {
            // ── Payroll & Statutory ──────────────────────────────────────
            'form16' => $this->form16($record, $filters),
            'statutory_register' => $this->statutoryRegister($filters),
            'bank_transfer_advice' => $this->bankTransferAdvice($filters),
            'salary_register' => $this->salaryRegister($filters),
            'bulk_payslips' => $this->bulkPayslips($filters),

            // ── Attendance & Leave ───────────────────────────────────────
            'muster_roll' => $this->musterRoll($filters),
            'daily_attendance' => $this->dailyAttendance($filters),
            'leave_card' => $this->leaveCard($record, $filters),
            'leave_sanction_slip' => ['request' => $record?->load('employee.department', 'leaveType', 'approver', 'splits.leaveType')],
            'regularization_slip' => ['request' => $record?->load('employee.department', 'reviewer')],
            'compoff_slip' => ['request' => $record?->load('employee.department', 'approver')],

            // ── Reports & Modules ────────────────────────────────────────
            'report_employee_master' => $this->reportEmployeeMaster($filters),
            'report_payroll' => $this->reportPayroll($filters),
            'report_leave' => $this->reportLeave($filters),
            'report_attendance' => $this->reportAttendance($filters),
            'report_expense_claims' => $this->reportExpenseClaims($filters),
            'budget_vs_actual' => $this->budgetVsActual($filters),
            'helpdesk_report' => $this->helpdeskReport($filters),
            'crm_pipeline' => $this->crmPipeline($filters),
            'report_builder_export' => $this->reportBuilderExport($record),
            'depreciation_schedule' => $this->depreciationSchedule($filters),
            'reimbursement_voucher' => ['claim' => $record?->load('employee.department', 'category', 'reviewer')],
            'requisition_note' => ['requisition' => $record?->load('requester', 'approvals.approver')],
            'service_job_card' => ['ticket' => $record?->load('customer', 'assignedTo', 'comments')],
            'asset_gate_pass' => ['asset' => $record?->load('category', 'location', 'custodian')],

            // ── HR Letters ───────────────────────────────────────────────
            'appointment_letter',
            'confirmation_letter',
            'experience_letter',
            'relieving_letter',
            'salary_certificate',
            'employee_id_card' => $this->employeeLetter($record),
            'full_and_final' => $this->fullAndFinal($record),
            'warning_letter', 'show_cause_notice' => ['warning' => $record?->load('employee.department', 'employee.designation', 'issuer')],

            // ── Sales & Finance ──────────────────────────────────────────
            'sales_order' => ['order' => $record?->load('customer', 'items.product', 'creator')],
            'payment_receipt' => ['payment' => $record?->load('invoice.customer', 'creator')],
            'goods_receipt_note' => ['grn' => $record?->load('purchaseOrder.vendor', 'items.product', 'creator')],
            'delivery_challan' => ['order' => $record?->load('customer', 'items.product')],
            'customer_statement' => $this->customerStatement($record, $filters),
            'vendor_statement' => $this->vendorStatement($record, $filters),
            'stock_ledger' => $this->stockLedger($filters),
            'credit_debit_note' => ['invoice' => $record?->load('customer', 'items.product', 'payments')],

            // ── Statutory Registers ──────────────────────────────────────
            'form_d_wage_register',
            'form_c_muster_roll',
            'form_e_deductions',
            'form_i_fines',
            'form_ii_damage_loss',
            'form_iia_advances',
            'form_iv_overtime',
            'form_a_lwf',
            'form_a_child_labour',
            'form_b_ease_of_compliance' => $this->statutoryRegisterForm($key, $filters),

            default => [],
        };
    }

    /** Model class behind a document's `subject`, for the record picker. */
    public function modelFor(string $subject): ?string
    {
        return match ($subject) {
            'employee' => Employee::class,
            'leave_request' => LeaveRequest::class,
            'regularization' => AttendanceRegularization::class,
            'comp_off' => CompOffRequest::class,
            'reimbursement' => ReimbursementClaim::class,
            'requisition' => Requisition::class,
            'service_ticket' => ServiceTicket::class,
            'asset' => Asset::class,
            'warning' => Warning::class,
            'sales_order' => SalesOrder::class,
            'payment' => Payment::class,
            'goods_receipt' => GoodsReceipt::class,
            'customer' => Customer::class,
            'vendor' => Vendor::class,
            'invoice' => Invoice::class,
            'report_template' => ReportTemplate::class,
            default => null,
        };
    }

    /**
     * Choices for the record picker on the Documents hub.
     *
     * @return Collection<int, array{id:int, label:string}>
     */
    public function optionsFor(string $subject, ?string $search = null): Collection
    {
        $limit = 200;

        return match ($subject) {
            'employee' => Employee::whereIn('status', ['active', 'probation', 'on_notice', 'resigned', 'exited'])
                ->when($search, fn ($q, $s) => $q->where(fn ($q) => $q
                    ->where('first_name', 'like', "%{$s}%")
                    ->orWhere('last_name', 'like', "%{$s}%")
                    ->orWhere('employee_code', 'like', "%{$s}%")))
                ->orderBy('first_name')->limit($limit)->get()
                ->map(fn ($e) => ['id' => $e->id, 'label' => $e->full_name.' ('.$e->employee_code.')']),

            'leave_request' => LeaveRequest::with('employee')->where('status', 'approved')
                ->latest()->limit($limit)->get()
                ->map(fn ($r) => ['id' => $r->id, 'label' => $r->request_code.' — '.($r->employee?->full_name ?? '')]),

            'regularization' => AttendanceRegularization::with('employee')->where('status', 'approved')
                ->latest()->limit($limit)->get()
                ->map(fn ($r) => ['id' => $r->id, 'label' => ($r->employee?->full_name ?? '').' — '.optional($r->date)->format('d M Y')]),

            'comp_off' => CompOffRequest::with('employee')->where('status', 'approved')
                ->latest()->limit($limit)->get()
                ->map(fn ($r) => ['id' => $r->id, 'label' => ($r->employee?->full_name ?? '').' — '.optional($r->comp_date)->format('d M Y')]),

            'reimbursement' => ReimbursementClaim::with('employee')->whereIn('status', ['approved', 'disbursed'])
                ->latest()->limit($limit)->get()
                ->map(fn ($c) => ['id' => $c->id, 'label' => $c->claim_code.' — '.($c->employee?->full_name ?? '')]),

            'requisition' => Requisition::latest()->limit($limit)->get()
                ->map(fn ($r) => ['id' => $r->id, 'label' => $r->requisition_code.' — '.$r->title]),

            'service_ticket' => ServiceTicket::with('customer')->latest()->limit($limit)->get()
                ->map(fn ($t) => ['id' => $t->id, 'label' => $t->ticket_number.' — '.($t->customer?->name ?? '')]),

            'asset' => Asset::latest()->limit($limit)->get()
                ->map(fn ($a) => ['id' => $a->id, 'label' => $a->asset_code.' — '.$a->name]),

            'warning' => Warning::with('employee')->latest()->limit($limit)->get()
                ->map(fn ($w) => ['id' => $w->id, 'label' => $w->warning_code.' — '.($w->employee?->full_name ?? '')]),

            'sales_order' => SalesOrder::with('customer')->latest()->limit($limit)->get()
                ->map(fn ($o) => ['id' => $o->id, 'label' => $o->order_number.' — '.($o->customer?->name ?? '')]),

            'payment' => Payment::with('invoice.customer')->latest()->limit($limit)->get()
                ->map(fn ($p) => ['id' => $p->id, 'label' => '₹'.number_format((float) $p->amount, 2).' — '.($p->invoice?->invoice_number ?? '')]),

            'goods_receipt' => GoodsReceipt::with('purchaseOrder.vendor')->latest()->limit($limit)->get()
                ->map(fn ($g) => ['id' => $g->id, 'label' => $g->grn_number.' — '.($g->purchaseOrder?->vendor?->name ?? '')]),

            'customer' => Customer::orderBy('name')->limit($limit)->get()
                ->map(fn ($c) => ['id' => $c->id, 'label' => $c->name.($c->company ? ' — '.$c->company : '')]),

            'vendor' => Vendor::orderBy('name')->limit($limit)->get()
                ->map(fn ($v) => ['id' => $v->id, 'label' => $v->name]),

            'invoice' => Invoice::with('customer')->latest()->limit($limit)->get()
                ->map(fn ($i) => ['id' => $i->id, 'label' => $i->invoice_number.' — '.($i->customer?->name ?? '')]),

            'report_template' => ReportTemplate::orderBy('name')->limit($limit)->get()
                ->map(fn ($t) => ['id' => $t->id, 'label' => $t->name]),

            default => collect(),
        };
    }

    // ═══ Payroll & Statutory ═════════════════════════════════════════════

    private function form16(?Model $employee, array $filters): array
    {
        $year = (int) ($filters['year'] ?? now()->year);

        // Indian financial year runs April to March.
        $start = Carbon::create($year, 4, 1)->startOfDay();
        $end = Carbon::create($year + 1, 3, 31)->endOfDay();

        $payslips = $employee
            ? Payslip::where('employee_id', $employee->id)
                ->whereBetween('period_start', [$start, $end])
                ->orderBy('period_start')->get()
            : collect();

        return [
            'employee' => $employee?->load('department', 'designation'),
            'payslips' => $payslips,
            'financialYear' => $year.'-'.substr((string) ($year + 1), 2),
            'periodStart' => $start,
            'periodEnd' => $end,
            'totals' => [
                'gross' => (float) $payslips->sum('gross_earnings'),
                'pf' => (float) $payslips->sum('pf'),
                'pt' => (float) $payslips->sum('professional_tax'),
                'tds' => (float) $payslips->sum('tds'),
                'esi' => (float) $payslips->sum('esi'),
                'net' => (float) $payslips->sum('net_pay'),
            ],
        ];
    }

    private function statutoryRegister(array $filters): array
    {
        return [
            'payslips' => $this->payslipsFor($filters)->load('employee.department'),
            'period' => $this->periodLabel($filters),
        ];
    }

    private function bankTransferAdvice(array $filters): array
    {
        $payslips = $this->payslipsFor($filters)->load('employee');

        return [
            'payslips' => $payslips,
            'period' => $this->periodLabel($filters),
            'total' => (float) $payslips->sum('net_pay'),
        ];
    }

    private function salaryRegister(array $filters): array
    {
        $payslips = $this->payslipsFor($filters)->load('employee.department');

        return [
            'payslips' => $payslips,
            'period' => $this->periodLabel($filters),
            'totals' => [
                'gross' => (float) $payslips->sum('gross_earnings'),
                'deductions' => (float) $payslips->sum('total_deductions'),
                'net' => (float) $payslips->sum('net_pay'),
            ],
        ];
    }

    private function bulkPayslips(array $filters): array
    {
        return [
            'payslips' => $this->payslipsFor($filters)->load('employee.department', 'employee.designation'),
            'period' => $this->periodLabel($filters),
        ];
    }

    /** @return Collection<int, Payslip> */
    private function payslipsFor(array $filters): Collection
    {
        $month = (int) ($filters['month'] ?? now()->month);
        $year = (int) ($filters['year'] ?? now()->year);

        return Payslip::where('month', $month)->where('year', $year)
            ->when($filters['department_id'] ?? null, fn ($q, $id) => $q
                ->whereHas('employee', fn ($e) => $e->where('department_id', $id)))
            ->get()
            ->sortBy(fn ($p) => $p->employee?->employee_code)
            ->values();
    }

    // ═══ Attendance & Leave ══════════════════════════════════════════════

    private function musterRoll(array $filters): array
    {
        $month = (int) ($filters['month'] ?? now()->month);
        $year = (int) ($filters['year'] ?? now()->year);
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();

        $employees = Employee::whereIn('status', ['active', 'probation', 'on_notice'])
            ->when($filters['department_id'] ?? null, fn ($q, $id) => $q->where('department_id', $id))
            ->orderBy('employee_code')->get();

        // The register reads the SAME resolved statuses as the calendar rather
        // than raw attendance rows. Reading rows directly was why a correction
        // an admin had applied — a week-off, a half-day/week-off, leave — did
        // not show up here: the resolution lives in the service, not the table.
        $attendance = app(AttendanceService::class);

        $statuses = $employees->mapWithKeys(fn (Employee $e) => [
            $e->id => collect($attendance->monthlyDayStatuses($e->id, $month, $year))
                ->mapWithKeys(fn ($status, $date) => [Carbon::parse($date)->day => $status]),
        ]);

        // Punch times still come off the rows, for the duty window the client
        // wants alongside the status.
        $records = Attendance::whereIn('employee_id', $employees->pluck('id'))
            ->whereBetween('date', [$start, $end])
            ->get()
            ->groupBy('employee_id')
            ->map(fn ($rows) => $rows->keyBy(fn ($r) => Carbon::parse($r->date)->day));

        return [
            'employees' => $employees,
            'statuses' => $statuses,
            'records' => $records,
            'daysInMonth' => $start->daysInMonth,
            'period' => $start->format('F Y'),
            'start' => $start,
        ];
    }

    private function dailyAttendance(array $filters): array
    {
        $date = Carbon::parse($filters['date'] ?? now()->toDateString());

        return [
            'records' => Attendance::with('employee.department')
                ->whereDate('date', $date)
                ->when($filters['department_id'] ?? null, fn ($q, $id) => $q
                    ->whereHas('employee', fn ($e) => $e->where('department_id', $id)))
                ->get()
                ->sortBy(fn ($r) => $r->employee?->employee_code)
                ->values(),
            'date' => $date,
        ];
    }

    private function leaveCard(?Model $employee, array $filters): array
    {
        $year = (int) ($filters['year'] ?? now()->year);

        return [
            'employee' => $employee?->load('department', 'designation'),
            'year' => $year,
            'balances' => $employee
                ? LeaveBalance::with('leaveType')->where('employee_id', $employee->id)->where('year', $year)->get()
                : collect(),
            'requests' => $employee
                ? LeaveRequest::with('leaveType')->where('employee_id', $employee->id)
                    ->whereYear('from_date', $year)->orderBy('from_date')->get()
                : collect(),
        ];
    }

    // ═══ Reports & Modules ═══════════════════════════════════════════════

    private function reportEmployeeMaster(array $filters): array
    {
        $rows = Employee::with('department', 'designation')
            ->when($filters['department_id'] ?? null, fn ($q, $id) => $q->where('department_id', $id))
            ->orderBy('employee_code')->get()
            ->map(fn (Employee $e) => [
                $e->employee_code, $e->full_name, $e->department?->name, $e->designation?->name,
                optional($e->joining_date)->format('d-m-Y'), ucfirst((string) $e->status),
                $e->phone, $e->email, $e->uan_number, $e->esi_number,
            ])->all();

        return $this->table(
            ['Emp ID', 'Name', 'Department', 'Designation', 'Joined', 'Status', 'Phone', 'Email', 'UAN', 'ESI'],
            $rows,
            ['Employees' => (string) count($rows)],
        );
    }

    private function reportPayroll(array $filters): array
    {
        $payslips = $this->payslipsFor($filters)->load('employee.department');

        $rows = $payslips->map(fn (Payslip $p) => [
            $p->employee?->employee_code, $p->employee?->full_name, $p->employee?->department?->name,
            number_format((float) $p->paid_days, 1), number_format((float) $p->gross_earnings, 2),
            number_format((float) $p->total_deductions, 2), number_format((float) $p->net_pay, 2),
            ucfirst((string) $p->status),
        ])->all();

        return $this->table(
            ['Emp ID', 'Name', 'Department', 'Paid Days', 'Gross', 'Deductions', 'Net Pay', 'Status'],
            $rows,
            [
                'Payslips' => (string) $payslips->count(),
                'Gross' => '₹'.number_format((float) $payslips->sum('gross_earnings'), 2),
                'Net' => '₹'.number_format((float) $payslips->sum('net_pay'), 2),
            ],
            $this->periodLabel($filters),
        );
    }

    private function reportLeave(array $filters): array
    {
        $year = (int) ($filters['year'] ?? now()->year);

        $rows = LeaveBalance::with('employee.department', 'leaveType')
            ->where('year', $year)
            ->when($filters['department_id'] ?? null, fn ($q, $id) => $q
                ->whereHas('employee', fn ($e) => $e->where('department_id', $id)))
            ->get()
            ->sortBy(fn ($b) => $b->employee?->employee_code)
            ->map(fn (LeaveBalance $b) => [
                $b->employee?->employee_code, $b->employee?->full_name, $b->employee?->department?->name,
                $b->leaveType?->name,
                number_format((float) $b->allocated, 1), number_format((float) $b->carried_forward, 1),
                number_format((float) $b->used, 1), number_format((float) $b->pending, 1),
                number_format($b->available, 1),
            ])->values()->all();

        return $this->table(
            ['Emp ID', 'Name', 'Department', 'Leave Type', 'Allocated', 'Carried', 'Used', 'Pending', 'Available'],
            $rows,
            ['Year' => (string) $year, 'Rows' => (string) count($rows)],
        );
    }

    private function reportAttendance(array $filters): array
    {
        $month = (int) ($filters['month'] ?? now()->month);
        $year = (int) ($filters['year'] ?? now()->year);
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();

        $employees = Employee::whereIn('status', ['active', 'probation', 'on_notice'])
            ->when($filters['department_id'] ?? null, fn ($q, $id) => $q->where('department_id', $id))
            ->orderBy('employee_code')->get();

        $tallies = Attendance::whereIn('employee_id', $employees->pluck('id'))
            ->whereBetween('date', [$start, $end])
            ->get()
            ->groupBy('employee_id')
            ->map(fn ($rows) => $rows->groupBy('status')->map->count());

        $rows = $employees->map(function (Employee $e) use ($tallies) {
            $t = $tallies->get($e->id, collect());

            return [
                $e->employee_code, $e->full_name, $e->department?->name,
                (int) ($t['present'] ?? 0), (int) ($t['half_day'] ?? 0),
                (int) ($t['absent'] ?? 0), (int) ($t['on_leave'] ?? 0),
                (int) ($t['week_off'] ?? 0), (int) ($t['holiday'] ?? 0),
            ];
        })->all();

        return $this->table(
            ['Emp ID', 'Name', 'Department', 'Present', 'Half Day', 'Absent', 'On Leave', 'Week Off', 'Holiday'],
            $rows,
            ['Employees' => (string) count($rows)],
            $start->format('F Y'),
        );
    }

    private function reportExpenseClaims(array $filters): array
    {
        $rows = ReimbursementClaim::with('employee', 'category')
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->whereDate('claim_date', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->whereDate('claim_date', '<=', $d))
            ->latest('claim_date')->get()
            ->map(fn (ReimbursementClaim $c) => [
                $c->claim_code, $c->employee?->full_name, $c->category?->name, $c->title,
                optional($c->claim_date)->format('d-m-Y'),
                number_format((float) $c->amount, 2),
                $c->approved_amount !== null ? number_format((float) $c->approved_amount, 2) : '—',
                ucfirst((string) $c->status),
            ])->all();

        return $this->table(
            ['Claim', 'Employee', 'Category', 'Title', 'Date', 'Claimed', 'Approved', 'Status'],
            $rows,
            ['Claims' => (string) count($rows)],
        );
    }

    private function budgetVsActual(array $filters): array
    {
        $rows = ExpenseBudget::with('category', 'employee')
            ->withSum('topups', 'amount')
            ->get()
            ->map(function (ExpenseBudget $b) {
                $allocated = (float) $b->amount + (float) ($b->topups_sum_amount ?? 0);
                $spent = (float) $b->expenses()->sum('amount');

                return [
                    $b->category?->name ?? '—',
                    $b->employee?->full_name ?? 'Department',
                    optional($b->period_start)->format('d-m-Y').' — '.optional($b->period_end)->format('d-m-Y'),
                    number_format($allocated, 2),
                    number_format($spent, 2),
                    number_format($allocated - $spent, 2),
                    $allocated > 0 ? round($spent / $allocated * 100).'%' : '—',
                ];
            })->all();

        return $this->table(
            ['Category', 'Owner', 'Period', 'Allocated', 'Spent', 'Remaining', 'Used'],
            $rows,
            ['Budgets' => (string) count($rows)],
        );
    }

    /**
     * The Helpdesk Report.
     *
     * Filters on the ticket's own department (a fixed hr/it/admin/accounts
     * enum, not the employees' Department table), plus a date, month and year
     * cut. The viewer's own department restriction is applied last and always
     * wins, so a filter can narrow what they see but never widen it.
     */
    private function helpdeskReport(array $filters): array
    {
        $f = fn (string $key) => ($filters[$key] ?? null) === '' ? null : ($filters[$key] ?? null);

        $query = InternalTicket::with('category', 'employee', 'assignee')
            ->when($f('ticket_department'), fn ($q, $d) => $q->where('department', $d))
            ->when($f('year'), fn ($q, $y) => $q->whereYear('internal_tickets.created_at', $y))
            ->when($f('month'), fn ($q, $m) => $q->whereMonth('internal_tickets.created_at', $m))
            ->when($f('date'), fn ($q, $d) => $q->whereDate('internal_tickets.created_at', $d));

        // Whatever was asked for, an admin restricted to certain departments
        // only ever gets those.
        $allowed = Auth::guard('admin')->user()?->helpdeskDepartments() ?? [];
        if ($allowed !== []) {
            $query->whereIn('department', $allowed);
        }

        $rows = $query->latest()->get()
            ->map(fn (InternalTicket $t) => [
                $t->ticket_number,
                $t->subject,
                // The client could not tell which department a downloaded
                // report belonged to; it is now the third column.
                InternalTicket::DEPARTMENTS[$t->department] ?? ucfirst((string) $t->department),
                $t->category?->name,
                // Employee code is its own column rather than being tacked onto
                // the name — these get matched against payroll, and a combined
                // cell cannot be sorted or looked up.
                $t->employee?->employee_code ?? '—',
                $t->employee?->full_name ?? '—',
                $t->assignee?->name ?? 'Unassigned',
                ucfirst(str_replace('_', ' ', (string) $t->status)),
                ucfirst((string) $t->priority),
                $t->created_at?->format('d-m-Y h:i A') ?? '—',
                $t->closed_at?->format('d-m-Y h:i A')
                    ?? $t->resolved_at?->format('d-m-Y h:i A')
                    ?? '—',
                $t->created_at ? (int) $t->created_at->diffInDays(now()).'d' : '—',
            ])->all();

        return $this->table(
            [
                'Ticket', 'Subject', 'Department', 'Category', 'Emp Code', 'Raised By',
                'Assigned To', 'Status', 'Priority', 'Generated Date & Time',
                'Closed Date & Time', 'Age',
            ],
            $rows,
            ['Tickets' => (string) count($rows)],
            $this->helpdeskPeriodLabel($filters),
        );
    }

    /** "September 2026 · IT" under the report heading, so a print says what it covers. */
    private function helpdeskPeriodLabel(array $filters): ?string
    {
        $parts = [];

        if (! empty($filters['date'])) {
            $parts[] = Carbon::parse($filters['date'])->format('d M Y');
        } elseif (! empty($filters['month']) || ! empty($filters['year'])) {
            $year = $filters['year'] ?? now()->year;
            $parts[] = empty($filters['month'])
                ? 'Year '.$year
                : Carbon::create((int) $year, (int) $filters['month'], 1)->format('F Y');
        }

        if (! empty($filters['ticket_department'])) {
            $parts[] = InternalTicket::DEPARTMENTS[$filters['ticket_department']]
                ?? ucfirst((string) $filters['ticket_department']);
        }

        return $parts ? implode(' · ', $parts) : null;
    }

    private function crmPipeline(array $filters): array
    {
        $leads = Lead::with('assignedTo')->get();

        $rows = $leads->map(fn (Lead $l) => [
            $l->code,
            $l->name,
            $l->company,
            ucfirst(str_replace('_', ' ', (string) $l->status)),
            $l->expected_value !== null ? number_format((float) $l->expected_value, 2) : '—',
            $l->assignedTo?->display_name ?? 'Unassigned',
            optional($l->next_follow_up_at)->format('d-m-Y') ?? '—',
        ])->all();

        return $this->table(
            ['Lead', 'Contact', 'Company', 'Stage', 'Value', 'Owner', 'Next Follow-up'],
            $rows,
            [
                'Leads' => (string) $leads->count(),
                'Pipeline value' => '₹'.number_format((float) $leads->sum('expected_value'), 2),
            ],
        );
    }

    private function reportBuilderExport(?Model $template): array
    {
        if (! $template) {
            return $this->table([], [], []);
        }

        // A saved template stores its own columns; the builder service knows how
        // to run it, so mirror what the on-screen export produces.
        $columns = (array) ($template->columns ?? []);
        $headings = array_map(fn ($c) => ucwords(str_replace('_', ' ', (string) $c)), $columns);

        return $this->table($headings, [], ['Template' => (string) $template->name], (string) $template->name);
    }

    private function depreciationSchedule(array $filters): array
    {
        $rows = Asset::with('category')
            ->whereNotNull('purchase_cost')
            ->orderBy('asset_code')->get()
            ->map(function (Asset $a) {
                $cost = (float) $a->purchase_cost;
                $accumulated = (float) ($a->accumulated_depreciation ?? 0);

                return [
                    $a->asset_code, $a->name, $a->category?->name,
                    optional($a->purchase_date)->format('d-m-Y'),
                    number_format($cost, 2),
                    ucfirst(str_replace('_', ' ', (string) $a->depreciation_method)),
                    $a->useful_life_years ? $a->useful_life_years.' yrs' : '—',
                    number_format($accumulated, 2),
                    number_format((float) ($a->current_book_value ?? ($cost - $accumulated)), 2),
                ];
            })->all();

        return $this->table(
            ['Asset Code', 'Asset', 'Category', 'Purchased', 'Cost', 'Method', 'Life', 'Accum. Dep.', 'Book Value'],
            $rows,
            ['Assets' => (string) count($rows)],
        );
    }

    // ═══ HR Letters ══════════════════════════════════════════════════════

    private function employeeLetter(?Model $employee): array
    {
        return [
            'employee' => $employee?->load('department', 'designation', 'currentSalary', 'reportingManager'),
            'salary' => $employee?->currentSalary,
        ];
    }

    private function fullAndFinal(?Model $employee): array
    {
        $employee?->load('department', 'designation', 'currentSalary');

        $lastPayslip = $employee
            ? Payslip::where('employee_id', $employee->id)->orderByDesc('year')->orderByDesc('month')->first()
            : null;

        $year = (int) now()->year;
        $balances = $employee
            ? LeaveBalance::with('leaveType')->where('employee_id', $employee->id)->where('year', $year)->get()
            : collect();

        // Encashable leave is the balance left on types marked encashable.
        $encashable = $balances->filter(fn (LeaveBalance $b) => (bool) $b->leaveType?->encashable);
        $perDay = $employee?->currentSalary
            ? round((float) $employee->currentSalary->gross_monthly / 30, 2)
            : 0.0;

        return [
            'employee' => $employee,
            'salary' => $employee?->currentSalary,
            'lastPayslip' => $lastPayslip,
            'balances' => $balances,
            'encashableDays' => (float) $encashable->sum(fn (LeaveBalance $b) => $b->available),
            'perDayRate' => $perDay,
        ];
    }

    // ═══ Sales & Finance ═════════════════════════════════════════════════

    private function customerStatement(?Model $customer, array $filters): array
    {
        $from = ! empty($filters['from']) ? Carbon::parse($filters['from']) : now()->startOfYear();
        $to = ! empty($filters['to']) ? Carbon::parse($filters['to']) : now();

        $invoices = $customer
            ? Invoice::where('customer_id', $customer->id)
                ->whereBetween('invoice_date', [$from, $to])
                ->orderBy('invoice_date')->get()
            : collect();

        $payments = $customer
            ? Payment::whereIn('invoice_id', $invoices->pluck('id'))
                ->orderBy('payment_date')->get()
            : collect();

        // Interleave invoices and payments into one dated ledger with a running
        // balance — that is what makes it a statement rather than two lists.
        $entries = $invoices->map(fn (Invoice $i) => [
            'date' => $i->invoice_date, 'ref' => $i->invoice_number, 'type' => 'Invoice',
            'debit' => (float) $i->grand_total, 'credit' => 0.0,
        ])->concat($payments->map(fn (Payment $p) => [
            'date' => $p->payment_date, 'ref' => $p->reference_no ?: 'Receipt',
            'type' => 'Payment', 'debit' => 0.0, 'credit' => (float) $p->amount,
        ]))->sortBy('date')->values();

        $running = 0.0;
        $entries = $entries->map(function ($row) use (&$running) {
            $running += $row['debit'] - $row['credit'];
            $row['balance'] = $running;

            return $row;
        });

        return [
            'customer' => $customer,
            'entries' => $entries,
            'from' => $from,
            'to' => $to,
            'closing' => $running,
        ];
    }

    private function vendorStatement(?Model $vendor, array $filters): array
    {
        $from = ! empty($filters['from']) ? Carbon::parse($filters['from']) : now()->startOfYear();
        $to = ! empty($filters['to']) ? Carbon::parse($filters['to']) : now();

        $orders = $vendor
            ? PurchaseOrder::where('vendor_id', $vendor->id)
                ->whereBetween('po_date', [$from, $to])
                ->orderBy('po_date')->get()
            : collect();

        return [
            'vendor' => $vendor,
            'orders' => $orders,
            'from' => $from,
            'to' => $to,
            'total' => (float) $orders->sum('grand_total'),
        ];
    }

    private function stockLedger(array $filters): array
    {
        $from = ! empty($filters['from']) ? Carbon::parse($filters['from']) : now()->startOfMonth();
        $to = ! empty($filters['to']) ? Carbon::parse($filters['to']) : now();

        $movements = StockMovement::with('product', 'warehouse')
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->orderBy('product_id')->orderBy('created_at')
            ->get();

        // Running quantity restarts per product, which is what a ledger shows.
        $running = [];
        $rows = $movements->map(function ($m) use (&$running) {
            $pid = $m->product_id;
            $running[$pid] = ($running[$pid] ?? 0) + ($m->type === 'out' ? -1 : 1) * (float) $m->quantity;

            return [
                $m->created_at?->format('d-m-Y'),
                $m->product?->sku,
                $m->product?->name,
                $m->warehouse?->name ?? '—',
                ucfirst((string) $m->type),
                number_format((float) $m->quantity, 2),
                number_format($running[$pid], 2),
                $m->notes ?? '—',
            ];
        })->all();

        return $this->table(
            ['Date', 'SKU', 'Product', 'Warehouse', 'Type', 'Quantity', 'Running Qty', 'Note'],
            $rows,
            ['Movements' => (string) count($rows)],
            $from->format('d M Y').' — '.$to->format('d M Y'),
        );
    }

    // ═══ Helpers ═════════════════════════════════════════════════════════

    /**
     * The shape every "report-table" document renders from.
     *
     * @param  array<int,string>  $headings
     * @param  array<int,array>  $rows
     * @param  array<string,string>  $summary
     */
    private function table(array $headings, array $rows, array $summary, ?string $period = null): array
    {
        return compact('headings', 'rows', 'summary', 'period');
    }

    // ── Statutory Registers (Module E) ───────────────────────────────────

    /**
     * The payload every statutory form needs: the establishment identity block,
     * the month in the three shapes the forms head themselves with, and the
     * rows for whichever register was asked for.
     *
     * @param  array{month?:int, year?:int}  $filters
     */
    private function statutoryRegisterForm(string $key, array $filters): array
    {
        $month = (int) ($filters['month'] ?? now()->month);
        $year = (int) ($filters['year'] ?? now()->year);

        $common = [
            'establishment' => $this->statutory->establishment(),
            'month' => $month,
            'year' => $year,
            'monthName' => Carbon::create($year, $month, 1)->format('F'),
            'monthLabel' => $this->statutory->monthLabel($month, $year),
            'periodLabel' => $this->statutory->periodLabel($month, $year),
            // Form C prints its heading per employee, so the layout must not
            // also pin one to every page.
            'repeatHeader' => $key !== 'form_c_muster_roll',
        ];

        return array_merge($common, match ($key) {
            'form_d_wage_register' => $this->statutory->wageRegister($month, $year),
            'form_c_muster_roll' => ['blocks' => $this->statutory->musterRoll($month, $year)],
            'form_e_deductions' => ['rows' => $this->statutory->deductionsRegister($month, $year)],
            'form_i_fines' => ['rows' => $this->statutory->finesRegister($month, $year)],
            'form_ii_damage_loss' => ['rows' => $this->statutory->damageRegister($month, $year)],
            'form_iia_advances' => ['rows' => $this->statutory->advancesRegister($month, $year)],
            'form_iv_overtime' => ['rows' => $this->statutory->overtimeRegister($month, $year)],
            'form_a_lwf' => ['rows' => $this->statutory->lwfRegister($month, $year)],
            'form_a_child_labour' => ['records' => $this->statutory->childLabourRegister($month, $year)],
            'form_b_ease_of_compliance' => $this->statutory->easeOfCompliance($month, $year),
            default => [],
        });
    }

    private function periodLabel(array $filters): string
    {
        $month = (int) ($filters['month'] ?? now()->month);
        $year = (int) ($filters['year'] ?? now()->year);

        return Carbon::create($year, $month, 1)->format('F Y');
    }
}
