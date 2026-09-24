<?php

namespace App\Support;

/**
 * Every generated document, in one place: the 42 of Module D plus the ten
 * statutory labour registers of Module E.
 *
 * Every document is declared here rather than hard-coded into a controller, so
 * the hub screen, the routes, the permission checks and the tests all read from
 * the same list. Adding a forty-third is a row here plus a Blade view.
 *
 * Each entry:
 *   pack        which of the five packs it belongs to
 *   name        what it is called on screen
 *   blurb       one line explaining what it is for
 *   view        the Blade template under resources/views/pdf/documents/
 *   scope       'record'  needs one record chosen (an employee, an invoice…)
 *               'period'  needs a month/year or a date range
 *               'filter'  a register — optional filters only
 *   subject     what a 'record' document is chosen from, drives the picker
 *   watermark   default watermark, if any
 *   landscape   print landscape (wide registers)
 *   permission  override the pack's permission module
 *   excel       also downloadable as .xlsx (table-shaped documents)
 *   template    Blade view path, when it does not live under pdf/documents
 *               — the statutory registers use their own prescribed layout,
 *               not the company letterhead
 */
class DocumentCatalog
{
    public const PACKS = [
        'payroll' => ['Payroll & Statutory', 'documents_payroll'],
        'attendance' => ['Attendance & Leave', 'documents_attendance'],
        'reports' => ['Reports & Modules', 'documents_reports'],
        'hr_letters' => ['HR Letters', 'documents_hr_letters'],
        'sales' => ['Sales & Finance', 'documents_sales'],
        'statutory' => ['Statutory Registers', 'documents_statutory'],
    ];

    /**
     * @return array<string, array{
     *   pack:string, name:string, blurb:string, view:string, scope:string,
     *   subject?:string, watermark?:string, landscape?:bool
     * }>
     */
    public static function all(): array
    {
        return array_merge(
            self::payrollPack(),
            self::attendancePack(),
            self::reportsPack(),
            self::hrLettersPack(),
            self::salesPack(),
            self::statutoryPack(),
        );
    }

    public static function find(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }

    /** @return array<string, array> documents belonging to one pack */
    public static function forPack(string $pack): array
    {
        return array_filter(self::all(), fn ($doc) => $doc['pack'] === $pack);
    }

    /**
     * The permission module that gates a document — normally its pack, but a
     * document may name its own when it needs to be handed out separately.
     */
    public static function permissionFor(string $key): ?string
    {
        $doc = self::find($key);

        if ($doc === null) {
            return null;
        }

        return $doc['permission'] ?? (self::PACKS[$doc['pack']][1] ?? null);
    }

    public static function count(): int
    {
        return count(self::all());
    }

    /**
     * The Blade view a document renders through.
     *
     * Module D documents live under pdf/documents and extend the company
     * letterhead. The statutory registers are prescribed forms and carry their
     * own layout, so they declare an explicit template instead.
     */
    public static function templateFor(string $key): ?string
    {
        $doc = self::find($key);

        if ($doc === null) {
            return null;
        }

        return $doc['template'] ?? 'pdf.documents.'.$doc['view'];
    }

    // ── Pack 1 · Payroll & Statutory (5) ─────────────────────────────────

    private static function payrollPack(): array
    {
        return [
            'form16' => [
                'pack' => 'payroll', 'name' => 'Form 16',
                'blurb' => 'Annual tax statement for one employee — Part A and Part B.',
                'view' => 'form16', 'scope' => 'record', 'subject' => 'employee',
            ],
            'statutory_register' => [
                'pack' => 'payroll', 'name' => 'PF / ESI / PT Register',
                'blurb' => 'Statutory contributions per employee for a month.',
                'view' => 'statutory-register', 'scope' => 'period', 'landscape' => true,
            ],
            'bank_transfer_advice' => [
                'pack' => 'payroll', 'name' => 'Bank Transfer Advice',
                'blurb' => 'Salary disbursement instruction to the bank, with account and IFSC.',
                'view' => 'bank-transfer-advice', 'scope' => 'period', 'landscape' => true,
            ],
            'salary_register' => [
                'pack' => 'payroll', 'name' => 'Salary Register',
                'blurb' => 'Full earnings and deductions for every employee in a month.',
                'view' => 'salary-register', 'scope' => 'period', 'landscape' => true,
            ],
            'bulk_payslips' => [
                'pack' => 'payroll', 'name' => 'Bulk Payslip Download',
                'blurb' => 'Every payslip for a month, one per page, in a single file.',
                'view' => 'bulk-payslips', 'scope' => 'period',
            ],
        ];
    }

    // ── Pack 2 · Attendance & Leave (6) ──────────────────────────────────

    private static function attendancePack(): array
    {
        return [
            'muster_roll' => [
                'pack' => 'attendance', 'name' => 'Monthly Attendance Register (Muster Roll)',
                'blurb' => 'Day-by-day attendance grid for the whole month.',
                'view' => 'muster-roll', 'scope' => 'period', 'landscape' => true,
            ],
            'daily_attendance' => [
                'pack' => 'attendance', 'name' => 'Daily Attendance Report',
                'blurb' => "One day's attendance with in / out times and worked hours.",
                'view' => 'daily-attendance', 'scope' => 'period',
            ],
            'leave_card' => [
                'pack' => 'attendance', 'name' => 'Leave Card',
                'blurb' => 'An employee\'s leave balance statement and every request for the year.',
                'view' => 'leave-card', 'scope' => 'record', 'subject' => 'employee',
            ],
            'leave_sanction_slip' => [
                'pack' => 'attendance', 'name' => 'Leave Sanction Slip',
                'blurb' => 'Approval slip for one leave request, with the paid / unpaid split.',
                'view' => 'leave-sanction-slip', 'scope' => 'record', 'subject' => 'leave_request',
            ],
            'regularization_slip' => [
                'pack' => 'attendance', 'name' => 'Regularization Approval Slip',
                'blurb' => 'Approval slip for an attendance correction.',
                'view' => 'regularization-slip', 'scope' => 'record', 'subject' => 'regularization',
            ],
            'compoff_slip' => [
                'pack' => 'attendance', 'name' => 'Comp-off Approval Slip',
                'blurb' => 'Approval slip for compensatory time off worked and claimed.',
                'view' => 'compoff-slip', 'scope' => 'record', 'subject' => 'comp_off',
            ],
        ];
    }

    // ── Pack 3 · Reports & Modules (14) ──────────────────────────────────

    private static function reportsPack(): array
    {
        return [
            'report_employee_master' => [
                'pack' => 'reports', 'name' => 'Employee Master Report',
                'blurb' => 'Every employee with department, designation and statutory numbers.',
                'view' => 'report-table', 'scope' => 'filter', 'landscape' => true,
            ],
            'report_payroll' => [
                'pack' => 'reports', 'name' => 'Payroll Report',
                'blurb' => 'Gross, deductions and net across a month.',
                'view' => 'report-table', 'scope' => 'period', 'landscape' => true,
            ],
            'report_leave' => [
                'pack' => 'reports', 'name' => 'Leave Report',
                'blurb' => 'Leave balances and consumption per employee.',
                'view' => 'report-table', 'scope' => 'filter', 'landscape' => true,
            ],
            'report_attendance' => [
                'pack' => 'reports', 'name' => 'Attendance Report',
                'blurb' => 'Present, absent, leave and late days per employee.',
                'view' => 'report-table', 'scope' => 'period', 'landscape' => true,
            ],
            'report_expense_claims' => [
                'pack' => 'reports', 'name' => 'Expense Claims Report',
                'blurb' => 'Reimbursement claims with status and amounts.',
                'view' => 'report-table', 'scope' => 'filter', 'landscape' => true,
            ],
            'budget_vs_actual' => [
                'pack' => 'reports', 'name' => 'Budget vs Actual Statement',
                'blurb' => 'Allocated, spent and remaining per budget.',
                'view' => 'report-table', 'scope' => 'filter', 'landscape' => true,
            ],
            'helpdesk_report' => [
                'pack' => 'reports', 'name' => 'Helpdesk Report',
                'blurb' => 'Internal tickets by department, category, status and age.',
                'view' => 'report-table', 'scope' => 'filter', 'landscape' => true,
                // Its own permission module: a department lead needs ticket
                // numbers without also getting payroll and CRM reports.
                'permission' => 'helpdesk_reports',
                // Ticket departments are their own enum, not the employee
                // Department table, so the hub renders a dedicated picker.
                'ticket_departments' => true,
                'excel' => true,
                // Taking the data out of the system is its own right.
                'export_permission' => 'helpdesk_reports.export',
            ],
            'crm_pipeline' => [
                'pack' => 'reports', 'name' => 'CRM Pipeline Report',
                'blurb' => 'Leads by stage with value and owner.',
                'view' => 'report-table', 'scope' => 'filter', 'landscape' => true,
            ],
            'report_builder_export' => [
                'pack' => 'reports', 'name' => 'Report Builder Export',
                'blurb' => 'Any saved custom report, rendered on the letterhead.',
                'view' => 'report-table', 'scope' => 'record', 'subject' => 'report_template',
                'landscape' => true,
            ],
            'reimbursement_voucher' => [
                'pack' => 'reports', 'name' => 'Reimbursement Voucher',
                'blurb' => 'Payment voucher for an approved expense claim.',
                'view' => 'reimbursement-voucher', 'scope' => 'record', 'subject' => 'reimbursement',
            ],
            'requisition_note' => [
                'pack' => 'reports', 'name' => 'Requisition Approval Note',
                'blurb' => 'Approval note for a requisition, with the approval chain.',
                'view' => 'requisition-note', 'scope' => 'record', 'subject' => 'requisition',
            ],
            'service_job_card' => [
                'pack' => 'reports', 'name' => 'Service Job Card',
                'blurb' => 'Work card for a service ticket, for the engineer to carry.',
                'view' => 'service-job-card', 'scope' => 'record', 'subject' => 'service_ticket',
            ],
            'asset_gate_pass' => [
                'pack' => 'reports', 'name' => 'Asset Gate Pass',
                'blurb' => 'Authorisation to move an asset off site, returnable or not.',
                'view' => 'asset-gate-pass', 'scope' => 'record', 'subject' => 'asset',
            ],
            'depreciation_schedule' => [
                'pack' => 'reports', 'name' => 'Asset Depreciation Schedule',
                'blurb' => 'Opening value, depreciation and closing value per asset.',
                'view' => 'report-table', 'scope' => 'filter', 'landscape' => true,
            ],
        ];
    }

    // ── Pack 4 · HR Letters (9) ──────────────────────────────────────────

    private static function hrLettersPack(): array
    {
        return [
            'appointment_letter' => [
                'pack' => 'hr_letters', 'name' => 'Appointment Letter',
                'blurb' => 'Formal appointment with role, date of joining and CTC.',
                'view' => 'letter-appointment', 'scope' => 'record', 'subject' => 'employee',
            ],
            'confirmation_letter' => [
                'pack' => 'hr_letters', 'name' => 'Confirmation Letter',
                'blurb' => 'Confirms an employee in service on completion of probation.',
                'view' => 'letter-confirmation', 'scope' => 'record', 'subject' => 'employee',
            ],
            'experience_letter' => [
                'pack' => 'hr_letters', 'name' => 'Experience Letter',
                'blurb' => 'Service certificate covering the full period of employment.',
                'view' => 'letter-experience', 'scope' => 'record', 'subject' => 'employee',
            ],
            'relieving_letter' => [
                'pack' => 'hr_letters', 'name' => 'Relieving Letter',
                'blurb' => 'Confirms relieving on the last working day, dues settled.',
                'view' => 'letter-relieving', 'scope' => 'record', 'subject' => 'employee',
            ],
            'salary_certificate' => [
                'pack' => 'hr_letters', 'name' => 'Salary Certificate',
                'blurb' => 'Certified salary with the full CTC annexure.',
                'view' => 'letter-salary-certificate', 'scope' => 'record', 'subject' => 'employee',
            ],
            'warning_letter' => [
                'pack' => 'hr_letters', 'name' => 'Warning Letter',
                'blurb' => 'Formal warning on record, with the incident and the expectation.',
                'view' => 'letter-warning', 'scope' => 'record', 'subject' => 'warning',
            ],
            'show_cause_notice' => [
                'pack' => 'hr_letters', 'name' => 'Show-cause Notice',
                'blurb' => 'Notice requiring a written explanation within a stated period.',
                'view' => 'letter-show-cause', 'scope' => 'record', 'subject' => 'warning',
            ],
            'employee_id_card' => [
                'pack' => 'hr_letters', 'name' => 'Employee ID Card',
                'blurb' => 'Printable ID card, front and back, with emergency details.',
                'view' => 'id-card', 'scope' => 'record', 'subject' => 'employee',
            ],
            'full_and_final' => [
                'pack' => 'hr_letters', 'name' => 'Full & Final Settlement',
                'blurb' => 'Final settlement statement of dues payable and recoverable.',
                'view' => 'full-and-final', 'scope' => 'record', 'subject' => 'employee',
            ],
        ];
    }

    // ── Pack 5 · Sales & Finance (8) ─────────────────────────────────────

    private static function salesPack(): array
    {
        return [
            'sales_order' => [
                'pack' => 'sales', 'name' => 'Sales Order',
                'blurb' => 'Confirmed order with line items, taxes and delivery terms.',
                'view' => 'sales-order', 'scope' => 'record', 'subject' => 'sales_order',
            ],
            'payment_receipt' => [
                'pack' => 'sales', 'name' => 'Payment Receipt',
                'blurb' => 'Acknowledgement of a payment received, amount in words.',
                'view' => 'payment-receipt', 'scope' => 'record', 'subject' => 'payment',
                'watermark' => 'PAID',
            ],
            'goods_receipt_note' => [
                'pack' => 'sales', 'name' => 'Goods Receipt Note',
                'blurb' => 'Records goods received against a purchase order.',
                'view' => 'goods-receipt-note', 'scope' => 'record', 'subject' => 'goods_receipt',
            ],
            'delivery_challan' => [
                'pack' => 'sales', 'name' => 'Delivery Challan',
                'blurb' => 'Accompanies goods in transit, with vehicle and dispatch details.',
                'view' => 'delivery-challan', 'scope' => 'record', 'subject' => 'sales_order',
            ],
            'customer_statement' => [
                'pack' => 'sales', 'name' => 'Customer Statement of Account',
                'blurb' => 'Invoices, payments and running balance for one customer.',
                'view' => 'customer-statement', 'scope' => 'record', 'subject' => 'customer',
            ],
            'vendor_statement' => [
                'pack' => 'sales', 'name' => 'Vendor Statement',
                'blurb' => 'Purchase orders and receipts for one vendor.',
                'view' => 'vendor-statement', 'scope' => 'record', 'subject' => 'vendor',
            ],
            'stock_ledger' => [
                'pack' => 'sales', 'name' => 'Stock Ledger',
                'blurb' => 'Every stock movement with running quantity per product.',
                'view' => 'stock-ledger', 'scope' => 'filter', 'landscape' => true,
            ],
            'credit_debit_note' => [
                'pack' => 'sales', 'name' => 'Credit / Debit Note',
                'blurb' => 'Adjustment note against an invoice, with reason and revised balance.',
                'view' => 'credit-debit-note', 'scope' => 'record', 'subject' => 'invoice',
            ],
        ];
    }

    // ── Pack 6 · Statutory Registers (10) ────────────────────────────────

    /**
     * The Punjab labour-law registers an establishment has to keep and produce
     * on inspection. Each is a prescribed form — the column set, the numbering
     * and the wording of the heading are fixed by the rules, not by us — so
     * they render through pdf/statutory rather than the letterhead layout.
     */
    private static function statutoryPack(): array
    {
        return [
            'form_d_wage_register' => [
                'pack' => 'statutory', 'name' => 'Form D · Register of Wages',
                'blurb' => 'Wages earned and deducted for every employee — Punjab Shops & Commercial Establishments Rules, 1958.',
                'view' => 'wage-register', 'template' => 'pdf.statutory.wage-register',
                'scope' => 'period', 'landscape' => true,
            ],
            'form_c_muster_roll' => [
                'pack' => 'statutory', 'name' => 'Form C · Register of Employees',
                'blurb' => 'One sheet per employee: spread-over, rest intervals, overtime and leave, day by day.',
                'view' => 'muster-roll', 'template' => 'pdf.statutory.muster-roll',
                'scope' => 'period', 'landscape' => true,
            ],
            'form_e_deductions' => [
                'pack' => 'statutory', 'name' => 'Form E · Register of Deductions',
                'blurb' => 'Every deduction made from wages, the fault behind it and what the money was used for.',
                'view' => 'deductions', 'template' => 'pdf.statutory.deductions',
                'scope' => 'period', 'landscape' => true,
            ],
            'form_i_fines' => [
                'pack' => 'statutory', 'name' => 'Form I · Register of Fines',
                'blurb' => 'Fines imposed, the offence, and whether the workman was heard — Punjab Minimum Wages Rules, 1950.',
                'view' => 'fines', 'template' => 'pdf.statutory.fines',
                'scope' => 'period', 'landscape' => true,
            ],
            'form_ii_damage_loss' => [
                'pack' => 'statutory', 'name' => 'Form II · Register of Damage or Loss',
                'blurb' => 'Deductions for damage or loss caused by an employee\'s neglect or default.',
                'view' => 'damage-loss', 'template' => 'pdf.statutory.damage-loss',
                'scope' => 'period', 'landscape' => true,
            ],
            'form_iia_advances' => [
                'pack' => 'statutory', 'name' => 'Form II-A · Register of Advances',
                'blurb' => 'Advances made to employees, their purpose and the instalments they repay in.',
                'view' => 'advances', 'template' => 'pdf.statutory.advances',
                'scope' => 'period', 'landscape' => true,
            ],
            'form_iv_overtime' => [
                'pack' => 'statutory', 'name' => 'Form IV · Overtime Register',
                'blurb' => 'Dates and extent of overtime worked, the rates applied and what was paid.',
                'view' => 'overtime', 'template' => 'pdf.statutory.overtime',
                'scope' => 'period', 'landscape' => true,
            ],
            'form_a_lwf' => [
                'pack' => 'statutory', 'name' => 'Form A · Labour Welfare Fund Register',
                'blurb' => 'Wages payable, deducted and paid — Punjab Labour Welfare Fund Act, 1965.',
                'view' => 'lwf-register', 'template' => 'pdf.statutory.lwf-register',
                'scope' => 'period', 'landscape' => true,
            ],
            'form_a_child_labour' => [
                'pack' => 'statutory', 'name' => 'Form A · Register of Child Labour',
                'blurb' => 'Children employed, if any — filed as a nil return when there are none.',
                'view' => 'child-labour', 'template' => 'pdf.statutory.child-labour',
                'scope' => 'period', 'landscape' => true,
            ],
            'form_b_ease_of_compliance' => [
                'pack' => 'statutory', 'name' => 'Form B · Ease of Compliance Wage Register',
                'blurb' => 'The combined central wage register under the Ease of Compliance Rules, 2017.',
                'view' => 'ease-of-compliance', 'template' => 'pdf.statutory.ease-of-compliance',
                'scope' => 'period', 'landscape' => true,
            ],
        ];
    }
}
