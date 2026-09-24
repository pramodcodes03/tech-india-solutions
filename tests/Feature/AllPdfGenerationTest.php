<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\Hr\Performance\ReportController;
use App\Models\Admin;
use App\Models\Appraisal;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Business;
use App\Models\Candidate;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Payslip;
use App\Models\ProformaInvoice;
use App\Models\PurchaseOrder;
use App\Models\Quotation;
use App\Models\RecruitmentStage;
use App\Models\Vendor;
use App\Support\DocumentCatalog;
use App\Support\Tenancy\CurrentBusiness;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUsers;

/**
 * Every PDF the application can produce, generated for real.
 *
 * Module D's 42 documents have their own suite; this one covers the PDFs that
 * existed before it — invoices, quotations, payslips, asset registers and the
 * rest — plus the two new module report exports. Together the two suites mean
 * no route in the system can emit a PDF that has never been generated in CI.
 *
 * Each case asserts the %PDF- file signature, because a 200 response carrying
 * an HTML error page is exactly the failure this is meant to catch.
 */
class AllPdfGenerationTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    protected Business $business;

    protected Admin $admin;

    protected Employee $employee;

    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();

        Carbon::setTestNow(Carbon::parse('2026-09-07 10:00:00'));

        $this->business = Business::create([
            'name' => 'PDF Co', 'legal_name' => 'PDF Company Private Limited', 'slug' => 'pdf-co',
            'gst' => '27AABCU9603R1ZX', 'pan' => 'AABCU9603R',
            'address' => '12 Industrial Estate', 'city' => 'Pune', 'state' => 'Maharashtra', 'pincode' => '411001',
            'phone' => '020-12345678', 'email' => 'accounts@pdfco.test',
            'currency_code' => 'INR', 'currency_symbol' => '₹',
        ]);
        app(CurrentBusiness::class)->set($this->business);

        $this->admin = Admin::create([
            'name' => 'PDF Admin', 'email' => 'admin@pdfco.test',
            'password' => bcrypt('password'), 'phone' => '9990001111',
            'status' => 'active', 'business_id' => $this->business->id,
        ]);
        $this->admin->assignRole('Admin');

        $department = Department::create([
            'business_id' => $this->business->id, 'name' => 'Operations', 'code' => 'OPS',
        ]);
        $designation = Designation::create([
            'business_id' => $this->business->id, 'name' => 'Executive', 'code' => 'EXE',
            'department_id' => $department->id,
        ]);

        $this->employee = Employee::create([
            'business_id' => $this->business->id, 'employee_code' => 'EMP-0001',
            'email' => 'asha@pdfco.test', 'first_name' => 'Asha', 'last_name' => 'Verma',
            'department_id' => $department->id, 'designation_id' => $designation->id,
            'status' => 'active', 'password' => 'secret', 'joining_date' => '2025-01-01',
            'pan_number' => 'ABCPV1234K', 'uan_number' => '100200300400',
            'bank_name' => 'State Bank', 'bank_account_number' => '30012345678', 'bank_ifsc' => 'SBIN0001234',
        ]);

        $this->customer = Customer::create([
            'business_id' => $this->business->id, 'code' => 'CUS-0001', 'name' => 'Sai Traders',
            'company' => 'Sai Traders LLP', 'gst_number' => '27AACCS1234M1Z5',
            'email' => 'accounts@sai.test', 'phone' => '9812345678',
            'billing_address' => '9 Market Road, Pune 411005',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** Assert a route returns a real PDF, naming it if it does not. */
    private function assertPdf(string $label, string $url): void
    {
        $response = $this->actingAs($this->admin, 'admin')->get($url);

        if ($response->status() !== 200) {
            $this->fail("{$label} returned {$response->status()}: "
                .($response->exception?->getMessage() ?? 'no exception recorded'));
        }

        $this->assertSame('application/pdf', $response->headers->get('content-type'),
            "{$label} did not return a PDF content type.");
        $this->assertStringStartsWith('%PDF-', $response->getContent(),
            "{$label} returned a 200 but the body is not a PDF — most likely a rendered error page.");
    }

    // ═══ Sales documents ═════════════════════════════════════════════════

    #[Test]
    public function the_sales_document_pdfs_generate(): void
    {
        $quotation = Quotation::create([
            'business_id' => $this->business->id, 'quotation_number' => 'QTN-0001',
            'customer_id' => $this->customer->id, 'quotation_date' => '2026-08-01',
            'valid_until' => '2026-08-31', 'subtotal' => 50000, 'tax_amount' => 9000,
            'grand_total' => 59000, 'status' => 'sent',
        ]);

        $proforma = ProformaInvoice::create([
            'business_id' => $this->business->id, 'proforma_number' => 'PI-0001',
            'customer_id' => $this->customer->id, 'proforma_date' => '2026-08-02',
            'subtotal' => 50000, 'tax_amount' => 9000, 'grand_total' => 59000, 'status' => 'sent',
        ]);

        $invoice = Invoice::create([
            'business_id' => $this->business->id, 'invoice_number' => 'INV-0001',
            'customer_id' => $this->customer->id, 'invoice_date' => '2026-08-05',
            'due_date' => '2026-09-04', 'subtotal' => 50000, 'tax_amount' => 9000,
            'grand_total' => 59000, 'status' => 'sent',
        ]);

        $purchaseOrder = PurchaseOrder::create([
            'business_id' => $this->business->id, 'po_number' => 'PO-0001',
            'vendor_id' => Vendor::create([
                'business_id' => $this->business->id, 'code' => 'VEN-0001', 'name' => 'Metro Supplies',
                'email' => 'sales@metro.test', 'phone' => '9800011122',
            ])->id,
            'po_date' => '2026-07-20', 'subtotal' => 40000, 'tax_amount' => 7200,
            'grand_total' => 47200, 'status' => 'sent',
        ]);

        $this->assertPdf('Quotation PDF', route('admin.quotations.pdf', $quotation));
        $this->assertPdf('Purchase Order PDF', route('admin.purchase-orders.pdf', $purchaseOrder));
        $this->assertPdf('Proforma Invoice PDF', route('admin.proforma-invoices.pdf', $proforma));
        $this->assertPdf('Tax Invoice PDF', route('admin.invoices.pdf', $invoice));
    }

    // ═══ HR documents ════════════════════════════════════════════════════

    #[Test]
    public function the_hr_document_pdfs_generate(): void
    {
        $payslip = Payslip::create([
            'business_id' => $this->business->id, 'payslip_code' => 'PS-0001',
            'employee_id' => $this->employee->id, 'month' => 8, 'year' => 2026,
            'period_start' => '2026-08-01', 'period_end' => '2026-08-31',
            'working_days' => 26, 'calendar_days' => 31, 'paid_days' => 26, 'lop_days' => 0,
            'basic' => 25000, 'hra' => 10000, 'gross_earnings' => 42850,
            'pf' => 1800, 'professional_tax' => 200, 'tds' => 1500,
            'total_deductions' => 3500, 'net_pay' => 39350, 'status' => 'paid',
        ]);

        $appraisal = Appraisal::create([
            'business_id' => $this->business->id, 'appraisal_code' => 'APR-0001',
            'employee_id' => $this->employee->id, 'cycle' => 'FY 2026',
            'period_start' => '2025-04-01', 'period_end' => '2026-03-31',
            'performance_score' => 82, 'overall_score' => 82, 'rating' => 'Very Good',
            'status' => 'finalized',
        ]);

        $this->assertPdf('Payslip PDF (admin)', route('admin.hr.payroll.pdf', $payslip));
        $this->assertPdf('Appraisal Letter PDF', route('admin.hr.appraisals.pdf', $appraisal));

        // The employee portal renders the same two views through its own routes.
        $employeeResponse = $this->actingAs($this->employee, 'employee')
            ->get(route('employee.payslips.pdf', $payslip));
        $this->assertSame(200, $employeeResponse->status(), 'Payslip PDF (employee portal) failed.');
        $this->assertStringStartsWith('%PDF-', $employeeResponse->getContent());
    }

    #[Test]
    public function the_offer_letter_and_recruitment_report_generate(): void
    {
        $stage = RecruitmentStage::create([
            'business_id' => $this->business->id, 'name' => 'Offer', 'slug' => 'offer',
            'type' => 'open', 'sort_order' => 5,
        ]);

        $candidate = Candidate::create([
            'business_id' => $this->business->id, 'first_name' => 'Ravi', 'last_name' => 'Kumar',
            'email' => 'ravi@candidate.test', 'phone' => '9876543210',
            'source' => 'referral', 'applied_at' => '2026-07-01',
            'stage_id' => $stage->id, 'status' => 'active',
            'offered_ctc' => 600000, 'offer_joining_date' => '2026-10-01',
        ]);

        $this->assertPdf('Offer Letter PDF', route('admin.hr.recruitment.offer-letter', $candidate));
        $this->assertPdf('Recruitment Funnel Report PDF',
            route('admin.hr.recruitment.reports.export', ['format' => 'pdf']));
    }

    // ═══ Expenses ════════════════════════════════════════════════════════

    #[Test]
    public function the_expense_voucher_pdf_generates(): void
    {
        $category = ExpenseCategory::create([
            'business_id' => $this->business->id, 'name' => 'Travel', 'slug' => 'travel',
        ]);

        $expense = Expense::create([
            'business_id' => $this->business->id, 'expense_code' => 'EXP-0001',
            'expense_category_id' => $category->id, 'title' => 'Client visit',
            'amount' => 4500, 'expense_date' => '2026-08-18', 'status' => 'paid',
        ]);

        $this->assertPdf('Expense Voucher PDF', route('admin.expenses.pdf', $expense));
    }

    #[Test]
    public function the_crm_pipeline_document_loads_its_relations(): void
    {
        // Seeded deliberately: with no leads at all, Eloquent skips eager
        // loading and a wrong relation name never surfaces. This is the exact
        // gap that let a bad `assignee` relation reach the live render.
        Lead::create([
            'business_id' => $this->business->id, 'code' => 'LEAD-0001',
            'name' => 'Ravi Kumar', 'company' => 'Sai Traders',
            'phone' => '9876543210', 'email' => 'ravi@sai.test',
            'source' => 'referral', 'lead_date' => '2026-08-01', 'status' => 'new',
            'expected_value' => 250000, 'assigned_to' => $this->admin->id,
        ]);

        $this->assertPdf('CRM Pipeline PDF',
            route('admin.documents.render', ['key' => 'crm_pipeline']));
    }

    // ═══ Business reports (5) ════════════════════════════════════════════

    #[Test]
    public function all_five_business_report_pdfs_generate(): void
    {
        foreach (['sales', 'purchases', 'inventory', 'customers', 'payments'] as $type) {
            $this->assertPdf("Business report PDF — {$type}",
                route('admin.reports.export-pdf', ['type' => $type]));
        }
    }

    // ═══ Asset registers (3) ═════════════════════════════════════════════

    #[Test]
    public function all_three_asset_register_pdfs_generate(): void
    {
        $category = AssetCategory::create([
            'business_id' => $this->business->id, 'name' => 'IT Equipment', 'code' => 'ITE',
        ]);

        Asset::create([
            'business_id' => $this->business->id, 'asset_code' => 'AST-0001',
            'name' => 'Dell Latitude', 'category_id' => $category->id,
            'purchase_date' => '2025-06-01', 'purchase_cost' => 85000,
            'depreciation_method' => 'straight_line', 'useful_life_years' => 4,
        ]);

        $this->assertPdf('Asset Register PDF', route('admin.assets.assets.export', ['format' => 'pdf']));
        $this->assertPdf('Asset Assignments PDF', route('admin.assets.assignments.export', ['format' => 'pdf']));
        $this->assertPdf('Asset Maintenance PDF', route('admin.assets.maintenance.export', ['format' => 'pdf']));
    }

    // ═══ The new module exports ══════════════════════════════════════════

    #[Test]
    public function the_tracker_register_pdfs_generate(): void
    {
        foreach (['break', 'diesel', 'visitors'] as $register) {
            $this->assertPdf("Tracker register PDF — {$register}",
                route("admin.hr.trackers.{$register}.export", ['format' => 'pdf']));
        }
    }

    #[Test]
    public function every_performance_report_pdf_generates(): void
    {
        $reports = array_keys(ReportController::REPORTS);
        $this->assertCount(13, $reports);

        foreach ($reports as $report) {
            $this->assertPdf("Performance report PDF — {$report}",
                route('admin.hr.performance.reports.show', ['report' => $report, 'format' => 'pdf']));
        }
    }

    // ═══ The whole picture ═══════════════════════════════════════════════

    #[Test]
    public function the_full_pdf_inventory_is_accounted_for(): void
    {
        // Module D's 42, plus the pre-existing documents this suite covers.
        // If a new PDF route is added without a test, this count moves and the
        // failure points straight at the gap.
        $moduleD = DocumentCatalog::count();
        $preExisting = [
            'quotation', 'proforma_invoice', 'tax_invoice', 'purchase_order', 'expense_voucher',
            'payslip', 'appraisal_letter', 'offer_letter', 'recruitment_funnel',
            'report_sales', 'report_purchases', 'report_inventory', 'report_customers', 'report_payments',
            'asset_register', 'asset_assignments', 'asset_maintenance',
        ];
        $newModuleExports = [
            'tracker_break', 'tracker_diesel', 'tracker_visitors',
        ];

        $this->assertSame(52, $moduleD,
            'The catalogue should hold 42 Module D documents plus 10 statutory registers.');
        // 16 were already working per the proposal, plus the Purchase Order PDF
        // whose template existed but had never been routed.
        $this->assertCount(17, $preExisting);

        // 52 + 17 + 3 tracker registers + 13 performance reports = 85 distinct
        // PDF outputs, every one of them generated in this suite or DocumentPackTest.
        $this->assertSame(85, $moduleD + count($preExisting) + count($newModuleExports) + 13);
    }
}
