<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Attendance;
use App\Models\AttendanceRegularization;
use App\Models\Business;
use App\Models\CompOffRequest;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\ExpenseCategory;
use App\Models\GoodsReceipt;
use App\Models\Invoice;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Payment;
use App\Models\Payslip;
use App\Models\PurchaseOrder;
use App\Models\ReimbursementClaim;
use App\Models\ReportTemplate;
use App\Models\Requisition;
use App\Models\SalaryStructure;
use App\Models\SalesOrder;
use App\Models\ServiceTicket;
use App\Models\Vendor;
use App\Models\Warning;
use App\Services\Documents\DocumentDataResolver;
use App\Support\AmountInWords;
use App\Support\DocumentCatalog;
use App\Support\Tenancy\CurrentBusiness;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUsers;

/**
 * Module D — the Letterhead Foundation and the 42-document pack.
 *
 * The headline test is `every_document_in_the_catalog_renders`: each of the 42
 * is generated for real through DOMPDF, because a document that throws on a
 * null relation is worse than one that looks plain.
 */
class DocumentPackTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    protected Business $business;

    protected Admin $admin;

    protected Employee $employee;

    /** Record ids by subject, so the render loop can supply one for each. */
    protected array $records = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();

        Carbon::setTestNow(Carbon::parse('2026-09-07 10:00:00'));

        $this->business = Business::create([
            'name' => 'Doc Co', 'legal_name' => 'Doc Company Private Limited', 'slug' => 'doc-co',
            'gst' => '27AABCU9603R1ZX', 'pan' => 'AABCU9603R', 'cin' => 'U72900MH2015PTC123456',
            'address' => '12 Industrial Estate', 'city' => 'Pune', 'state' => 'Maharashtra', 'pincode' => '411001',
            'phone' => '020-12345678', 'email' => 'accounts@docco.test',
            'currency_code' => 'INR', 'currency_symbol' => '₹',
            'letterhead_footer' => 'Registered office: Pune · CIN U72900MH2015PTC123456',
        ]);
        app(CurrentBusiness::class)->set($this->business);

        $this->admin = Admin::create([
            'name' => 'Doc Admin', 'email' => 'admin@docco.test',
            'password' => bcrypt('password'), 'phone' => '9990001111',
            'status' => 'active', 'business_id' => $this->business->id,
        ]);
        $this->admin->assignRole('Admin');

        $this->seedRecords();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * One record of every kind a document can be generated from, so the render
     * loop exercises real relations rather than empty templates.
     */
    private function seedRecords(): void
    {
        $department = Department::create([
            'business_id' => $this->business->id, 'name' => 'Operations', 'code' => 'OPS',
        ]);
        $designation = Designation::create([
            'business_id' => $this->business->id, 'name' => 'Executive', 'code' => 'EXE',
            'department_id' => $department->id,
        ]);

        $this->employee = Employee::create([
            'business_id' => $this->business->id, 'employee_code' => 'EMP-0001',
            'email' => 'asha@docco.test', 'first_name' => 'Asha', 'last_name' => 'Verma',
            'department_id' => $department->id, 'designation_id' => $designation->id,
            'status' => 'active', 'password' => 'secret', 'joining_date' => '2025-01-01',
            'phone' => '9876543210', 'gender' => 'female', 'blood_group' => 'O+',
            'pan_number' => 'ABCPV1234K', 'uan_number' => '100200300400', 'esi_number' => '3100123456',
            'bank_name' => 'State Bank', 'bank_account_number' => '30012345678', 'bank_ifsc' => 'SBIN0001234',
            'current_address' => '4 Rose Lane, Pune 411002',
        ]);
        $this->records['employee'] = $this->employee->id;

        SalaryStructure::create([
            'business_id' => $this->business->id, 'employee_id' => $this->employee->id,
            'basic' => 25000, 'hra' => 10000, 'conveyance' => 1600, 'medical' => 1250, 'special' => 5000,
            'gross_monthly' => 42850, 'ctc_annual' => 514200,
            'effective_from' => '2025-01-01', 'is_current' => true, 'status' => 'approved',
        ]);

        Payslip::create([
            'business_id' => $this->business->id, 'payslip_code' => 'PS-0001',
            'employee_id' => $this->employee->id, 'month' => 8, 'year' => 2026,
            'period_start' => '2026-08-01', 'period_end' => '2026-08-31',
            'working_days' => 26, 'calendar_days' => 31, 'paid_days' => 26, 'lop_days' => 0,
            'basic' => 25000, 'hra' => 10000, 'conveyance' => 1600, 'medical' => 1250, 'special' => 5000,
            'gross_earnings' => 42850, 'pf' => 1800, 'esi' => 0, 'professional_tax' => 200, 'tds' => 1500,
            'total_deductions' => 3500, 'net_pay' => 39350, 'status' => 'paid',
        ]);

        Attendance::create([
            'business_id' => $this->business->id, 'employee_id' => $this->employee->id,
            'date' => '2026-09-07', 'check_in' => '09:30:00', 'check_out' => '18:45:00',
            'worked_hours' => 9.25, 'status' => 'present', 'source' => 'manual',
        ]);

        $leaveType = LeaveType::create([
            'business_id' => $this->business->id, 'code' => 'CL', 'name' => 'Casual Leave',
            'annual_quota' => 12, 'is_paid' => true, 'encashable' => true, 'status' => 'active',
        ]);
        LeaveBalance::create([
            'business_id' => $this->business->id, 'employee_id' => $this->employee->id,
            'leave_type_id' => $leaveType->id, 'year' => 2026,
            'allocated' => 12, 'used' => 2, 'pending' => 0, 'carried_forward' => 3,
        ]);
        $this->records['leave_request'] = LeaveRequest::create([
            'business_id' => $this->business->id, 'request_code' => 'LR-0001',
            'employee_id' => $this->employee->id, 'leave_type_id' => $leaveType->id,
            'from_date' => '2026-08-10', 'to_date' => '2026-08-11', 'days' => 2,
            'paid_days' => 2, 'unpaid_days' => 0, 'day_portion' => 'full',
            'reason' => 'Family function', 'status' => 'approved', 'actioned_at' => now(),
        ])->id;

        $this->records['regularization'] = AttendanceRegularization::create([
            'business_id' => $this->business->id, 'employee_id' => $this->employee->id,
            'date' => '2026-08-12', 'requested_check_in' => '09:15:00', 'requested_check_out' => '18:30:00',
            'reason' => 'Biometric not captured', 'status' => 'approved', 'actioned_at' => now(),
        ])->id;

        $this->records['comp_off'] = CompOffRequest::create([
            'business_id' => $this->business->id, 'employee_id' => $this->employee->id,
            'worked_on' => '2026-08-16', 'comp_date' => '2026-08-24',
            'reason' => 'Worked on a Sunday for the release', 'status' => 'approved', 'actioned_at' => now(),
        ])->id;

        $this->records['warning'] = Warning::create([
            'business_id' => $this->business->id, 'employee_id' => $this->employee->id,
            'warning_code' => 'WRN-0001', 'title' => 'Repeated late arrival', 'level' => 1,
            'reason' => 'Arrived after 10:30 on four occasions in August.',
            'issued_on' => '2026-08-20', 'status' => 'active',
        ])->id;

        $expenseCategory = ExpenseCategory::create([
            'business_id' => $this->business->id, 'name' => 'Travel', 'slug' => 'travel',
        ]);
        $this->records['reimbursement'] = ReimbursementClaim::create([
            'business_id' => $this->business->id, 'claim_code' => 'RMB-0001',
            'employee_id' => $this->employee->id, 'expense_category_id' => $expenseCategory->id,
            'title' => 'Client visit — Mumbai', 'purpose' => 'Travel and lodging',
            'amount' => 4500, 'approved_amount' => 4200, 'claim_date' => '2026-08-18', 'status' => 'approved',
        ])->id;

        $this->records['requisition'] = Requisition::create([
            'business_id' => $this->business->id, 'requisition_code' => 'REQ-0001',
            'requested_by' => $this->admin->id, 'category' => 'it_equipment',
            'title' => 'Two additional monitors', 'purpose' => 'New joiners in Operations',
            'requested_amount' => 24000, 'estimated_amount' => 22000, 'status' => 'approved',
        ])->id;

        $customer = Customer::create([
            'business_id' => $this->business->id, 'code' => 'CUS-0001', 'name' => 'Sai Traders',
            'company' => 'Sai Traders LLP', 'gst_number' => '27AACCS1234M1Z5',
            'email' => 'accounts@saitraders.test', 'phone' => '9812345678',
            'billing_address' => '9 Market Road, Pune 411005',
        ]);
        $this->records['customer'] = $customer->id;

        $this->records['vendor'] = Vendor::create([
            'business_id' => $this->business->id, 'code' => 'VEN-0001', 'name' => 'Metro Supplies',
            'email' => 'sales@metro.test', 'phone' => '9800011122',
        ])->id;

        $invoice = Invoice::create([
            'business_id' => $this->business->id, 'invoice_number' => 'INV-0001',
            'customer_id' => $customer->id, 'invoice_date' => '2026-08-05', 'due_date' => '2026-09-04',
            'subtotal' => 50000, 'tax_amount' => 9000, 'grand_total' => 59000, 'status' => 'sent',
        ]);
        $this->records['invoice'] = $invoice->id;

        $this->records['payment'] = Payment::create([
            'business_id' => $this->business->id, 'payment_number' => 'PAY-0001',
            'invoice_id' => $invoice->id, 'customer_id' => $customer->id,
            'payment_date' => '2026-08-20', 'amount' => 30000, 'mode' => 'bank_transfer',
            'reference_no' => 'UTR123456789',
        ])->id;

        $this->records['sales_order'] = SalesOrder::create([
            'business_id' => $this->business->id, 'order_number' => 'SO-0001',
            'customer_id' => $customer->id, 'order_date' => '2026-08-02', 'status' => 'confirmed',
            'subtotal' => 50000, 'tax_amount' => 9000, 'grand_total' => 59000,
        ])->id;

        $purchaseOrder = PurchaseOrder::create([
            'business_id' => $this->business->id, 'po_number' => 'PO-0001',
            'vendor_id' => $this->records['vendor'], 'po_date' => '2026-07-20',
            'subtotal' => 40000, 'tax_amount' => 7200, 'grand_total' => 47200, 'status' => 'received',
        ]);
        $this->records['goods_receipt'] = GoodsReceipt::create([
            'business_id' => $this->business->id, 'grn_number' => 'GRN-0001',
            'purchase_order_id' => $purchaseOrder->id, 'received_date' => '2026-07-28',
            'notes' => 'Received in full, no damage.',
        ])->id;

        $assetCategory = AssetCategory::create([
            'business_id' => $this->business->id, 'name' => 'IT Equipment', 'code' => 'ITE',
        ]);
        $this->records['asset'] = Asset::create([
            'business_id' => $this->business->id, 'asset_code' => 'AST-0001',
            'name' => 'Dell Latitude 5440', 'serial_number' => 'SN-99887766',
            'category_id' => $assetCategory->id, 'purchase_date' => '2025-06-01',
            'purchase_cost' => 85000, 'depreciation_method' => 'straight_line',
            'useful_life_years' => 4, 'accumulated_depreciation' => 21250, 'current_book_value' => 63750,
        ])->id;

        $this->records['report_template'] = ReportTemplate::create([
            'business_id' => $this->business->id, 'name' => 'Active employees by department',
            'module' => 'employees', 'columns' => ['employee_code', 'full_name', 'department'],
            'filters' => ['status' => 'active'],
        ])->id;

        $this->records['service_ticket'] = ServiceTicket::create([
            'business_id' => $this->business->id, 'ticket_number' => 'TKT-0001',
            'customer_id' => $customer->id, 'subject' => 'Printer not responding',
            'issue_description' => 'Reported jammed and offline since Monday.',
            'opened_at' => '2026-09-01 09:00:00',
            'priority' => 'high', 'status' => 'open',
        ])->id;
    }

    // ═══ The catalogue ═══════════════════════════════════════════════════

    #[Test]
    public function the_catalogue_holds_module_d_plus_the_statutory_registers(): void
    {
        // 42 from the proposal, plus the 10 Punjab statutory registers of
        // Module E, which share the same catalogue and render route.
        $this->assertSame(52, DocumentCatalog::count());
    }

    #[Test]
    public function the_five_packs_hold_the_promised_counts(): void
    {
        // From the proposal: 5 + 6 + 14 + 9 + 8 = 42.
        $this->assertCount(5, DocumentCatalog::forPack('payroll'));
        $this->assertCount(6, DocumentCatalog::forPack('attendance'));
        $this->assertCount(14, DocumentCatalog::forPack('reports'));
        $this->assertCount(9, DocumentCatalog::forPack('hr_letters'));
        $this->assertCount(8, DocumentCatalog::forPack('sales'));
        // Module E adds a sixth pack of 10 prescribed labour registers.
        $this->assertCount(10, DocumentCatalog::forPack('statutory'));
    }

    #[Test]
    public function every_document_has_a_template_on_disk(): void
    {
        foreach (DocumentCatalog::all() as $key => $doc) {
            // Not every document lives under pdf/documents — the statutory
            // registers declare their own template, so ask the catalogue
            // rather than assuming the path.
            $view = DocumentCatalog::templateFor($key);

            $this->assertFileExists(
                resource_path('views/'.str_replace('.', '/', $view).'.blade.php'),
                "Document {$key} points at a template that does not exist.",
            );
        }
    }

    #[Test]
    public function every_document_declares_a_pack_and_a_permission(): void
    {
        foreach (DocumentCatalog::all() as $key => $doc) {
            $this->assertArrayHasKey($doc['pack'], DocumentCatalog::PACKS, "Unknown pack on {$key}.");
            $this->assertNotNull(DocumentCatalog::permissionFor($key), "No permission resolved for {$key}.");
            $this->assertContains($doc['scope'], ['record', 'period', 'filter'], "Bad scope on {$key}.");

            if ($doc['scope'] === 'record') {
                $this->assertArrayHasKey('subject', $doc, "Record-scoped {$key} has no subject.");
                $this->assertNotNull(
                    app(DocumentDataResolver::class)->modelFor($doc['subject']),
                    "No model resolves for {$key}'s subject.",
                );
            }
        }
    }

    // ═══ The render loop — the headline test ═════════════════════════════

    #[Test]
    public function every_document_in_the_catalog_renders(): void
    {
        foreach (DocumentCatalog::all() as $key => $doc) {
            $params = ['month' => 8, 'year' => 2026, 'date' => '2026-09-07',
                'from' => '2026-01-01', 'to' => '2026-12-31'];

            if (($doc['scope'] ?? '') === 'record') {
                $recordId = $this->records[$doc['subject']] ?? null;
                $this->assertNotNull($recordId, "No fixture record seeded for {$key} ({$doc['subject']}).");
                $params['record'] = $recordId;
            }

            $response = $this->actingAs($this->admin, 'admin')
                ->get(route('admin.documents.render', array_merge(['key' => $key], $params)));

            if ($response->status() !== 200) {
                $this->fail("Document {$key} returned {$response->status()}: "
                    .($response->exception?->getMessage() ?? 'no exception recorded'));
            }
            $this->assertSame('application/pdf', $response->headers->get('content-type'),
                "Document {$key} did not return a PDF.");

            // %PDF- is the file signature; anything else means DOMPDF produced
            // an error page rather than a document.
            $this->assertStringStartsWith('%PDF-', $response->getContent(),
                "Document {$key} did not produce a valid PDF.");
        }
    }

    #[Test]
    public function a_record_document_without_a_record_is_refused(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.documents.render', ['key' => 'appointment_letter']))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    #[Test]
    public function an_unknown_document_key_is_a_404(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.documents.render', ['key' => 'not_a_real_document']))
            ->assertStatus(404);
    }

    #[Test]
    public function a_record_from_another_business_is_not_reachable(): void
    {
        $other = Business::create([
            'name' => 'Other Co', 'slug' => 'other-doc-co',
            'currency_code' => 'INR', 'currency_symbol' => '₹',
        ]);
        $theirs = Employee::create([
            'business_id' => $other->id, 'employee_code' => 'EMP-X',
            'email' => 'x@other.test', 'first_name' => 'Their', 'last_name' => 'Employee',
            'status' => 'active', 'password' => 'secret', 'joining_date' => '2025-01-01',
        ]);

        // The tenancy scope means the record simply is not found — nothing leaks.
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.documents.render', ['key' => 'appointment_letter', 'record' => $theirs->id]))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    // ═══ Letterhead Foundation ═══════════════════════════════════════════

    #[Test]
    public function amount_in_words_uses_indian_numbering(): void
    {
        // Lakh and crore, not million and billion — every document here is an
        // Indian statutory or commercial form.
        $this->assertSame(
            'Rupees Twelve Lakh Thirty Four Thousand Five Hundred Sixty Seven and Eighty Nine Paise Only',
            AmountInWords::currency(1234567.89),
        );
        $this->assertSame('Rupees One Crore Only', AmountInWords::currency(10000000));
        $this->assertSame('Rupees Zero Only', AmountInWords::currency(0));
    }

    #[Test]
    public function amount_in_words_handles_the_awkward_numbers(): void
    {
        $this->assertSame('Rupees Fifteen Only', AmountInWords::currency(15));          // teens
        $this->assertSame('Rupees One Hundred One Only', AmountInWords::currency(101)); // no "and"
        $this->assertSame('Rupees Zero and Fifty Paise Only', AmountInWords::currency(0.5));
        // Rounding must carry into the rupee rather than reading "Ninety Nine Paise".
        $this->assertSame('Rupees Ten Only', AmountInWords::currency(9.999));
        $this->assertStringStartsWith('Minus Rupees', AmountInWords::currency(-250.75));
    }

    #[Test]
    public function the_letterhead_prints_the_business_identity(): void
    {
        $html = $this->renderHtml('appointment_letter', ['record' => $this->employee->id]);

        $this->assertStringContainsString('Doc Company Private Limited', $html);
        $this->assertStringContainsString('27AABCU9603R1ZX', $html, 'GSTIN should be on the letterhead.');
        $this->assertStringContainsString('AABCU9603R', $html, 'PAN should be on the letterhead.');
        $this->assertStringContainsString('Registered office: Pune', $html, 'Footer line should print.');
        $this->assertStringContainsString('counter(page)', $html, 'Page numbering should be present.');
    }

    #[Test]
    public function turning_the_letterhead_off_leaves_it_out(): void
    {
        $this->business->update(['letterhead_enabled' => false]);

        $html = $this->renderHtml('appointment_letter', ['record' => $this->employee->id]);

        // For businesses printing onto pre-printed paper, nothing should overlap.
        $this->assertStringNotContainsString('Registered office: Pune', $html);
        $this->assertStringNotContainsString('<div class="lh-header">', $html);
        // The document body itself is untouched.
        $this->assertStringContainsString('Appointment Letter', $html);
    }

    #[Test]
    public function a_watermark_is_printed_when_asked_for(): void
    {
        $html = $this->renderHtml('sales_order', [
            'record' => $this->records['sales_order'], 'watermark' => 'DRAFT',
        ]);

        $this->assertStringContainsString('watermark', $html);
        $this->assertStringContainsString('DRAFT', $html);
    }

    #[Test]
    public function the_payment_receipt_carries_its_paid_watermark_by_default(): void
    {
        $html = $this->renderHtml('payment_receipt', ['record' => $this->records['payment']]);

        $this->assertStringContainsString('PAID', $html);
    }

    #[Test]
    public function the_signature_and_seal_are_printed_once_uploaded(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin, 'admin')->post(route('admin.documents.letterhead.save'), [
            'signatory_name' => 'Priya Sharma',
            'signatory_role' => 'Head — Human Resources',
            'letterhead_enabled' => 1,
            'signature' => UploadedFile::fake()->image('sign.png', 300, 100),
            'seal' => UploadedFile::fake()->image('seal.png', 200, 200),
        ])->assertRedirect()->assertSessionHas('success');

        $this->business->refresh();
        $this->assertNotNull($this->business->signature_path);
        $this->assertNotNull($this->business->seal_path);
        Storage::disk('public')->assertExists($this->business->signature_path);

        $html = $this->renderHtml('experience_letter', ['record' => $this->employee->id]);
        $this->assertStringContainsString('Priya Sharma', $html);
        $this->assertStringContainsString('Head — Human Resources', $html);
    }

    #[Test]
    public function the_signature_can_be_removed_again(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin, 'admin')->post(route('admin.documents.letterhead.save'), [
            'letterhead_enabled' => 1,
            'signature' => UploadedFile::fake()->image('sign.png'),
        ]);
        $this->assertNotNull($this->business->fresh()->signature_path);

        $this->actingAs($this->admin, 'admin')->post(route('admin.documents.letterhead.save'), [
            'letterhead_enabled' => 1,
            'remove_signature' => 1,
        ]);
        $this->assertNull($this->business->fresh()->signature_path);
    }

    #[Test]
    public function an_oversized_or_wrong_type_signature_is_refused(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin, 'admin')->post(route('admin.documents.letterhead.save'), [
            'signature' => UploadedFile::fake()->create('sign.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('signature');

        $this->assertNull($this->business->fresh()->signature_path);
    }

    // ═══ Content spot-checks ═════════════════════════════════════════════

    #[Test]
    public function the_salary_certificate_carries_the_ctc_annexure_in_words(): void
    {
        $html = $this->renderHtml('salary_certificate', ['record' => $this->employee->id]);

        $this->assertStringContainsString('Annexure', $html);
        $this->assertStringContainsString('514,200.00', $html, 'The annual CTC should be shown.');
        $this->assertStringContainsString('Five Lakh Fourteen Thousand Two Hundred', $html,
            'The CTC should also be spelled out in Indian format.');
    }

    #[Test]
    public function the_bank_transfer_advice_flags_missing_bank_details(): void
    {
        // A second employee with a payslip but no bank details.
        $incomplete = Employee::create([
            'business_id' => $this->business->id, 'employee_code' => 'EMP-0002',
            'email' => 'nobank@docco.test', 'first_name' => 'No', 'last_name' => 'Bank',
            'status' => 'active', 'password' => 'secret', 'joining_date' => '2025-06-01',
        ]);
        Payslip::create([
            'business_id' => $this->business->id, 'payslip_code' => 'PS-0002',
            'employee_id' => $incomplete->id, 'month' => 8, 'year' => 2026,
            'period_start' => '2026-08-01', 'period_end' => '2026-08-31',
            'working_days' => 26, 'paid_days' => 26, 'lop_days' => 0,
            'basic' => 20000, 'gross_earnings' => 30000, 'total_deductions' => 2000,
            'net_pay' => 28000, 'status' => 'generated',
        ]);

        $html = $this->renderHtml('bank_transfer_advice', ['month' => 8, 'year' => 2026]);

        // Named rather than silently dropped — a short bank file is worse than
        // one that says who is missing.
        $this->assertStringContainsString('incomplete bank details', $html);
        $this->assertStringContainsString('No Bank', $html);
    }

    #[Test]
    public function the_muster_roll_grids_the_whole_month(): void
    {
        $html = $this->renderHtml('muster_roll', ['month' => 9, 'year' => 2026]);

        $this->assertStringContainsString('Monthly Attendance Register', $html);
        // September has 30 days, so the header must run to 30.
        $this->assertStringContainsString('>30<', $html);
        $this->assertStringContainsString('Present', $html, 'The legend should be printed.');
    }

    #[Test]
    public function the_leave_sanction_slip_shows_the_paid_unpaid_split(): void
    {
        $html = $this->renderHtml('leave_sanction_slip', ['record' => $this->records['leave_request']]);

        $this->assertStringContainsString('Sanctioned as paid leave', $html);
        $this->assertStringContainsString('Sanctioned as unpaid', $html);
        $this->assertStringContainsString('APPROVED', $html);
    }

    #[Test]
    public function the_customer_statement_runs_a_balance(): void
    {
        $html = $this->renderHtml('customer_statement', [
            'record' => $this->records['customer'], 'from' => '2026-01-01', 'to' => '2026-12-31',
        ]);

        $this->assertStringContainsString('Statement of Account', $html);
        $this->assertStringContainsString('INV-0001', $html);
        // 59,000 invoiced less 30,000 received leaves 29,000 outstanding.
        $this->assertStringContainsString('29,000.00', $html);
    }

    #[Test]
    public function form16_computes_against_the_financial_year(): void
    {
        $html = $this->renderHtml('form16', ['record' => $this->employee->id, 'year' => 2026]);

        $this->assertStringContainsString('2026-27', $html, 'The financial year should read 2026-27.');
        $this->assertStringContainsString('AABCU9603R', $html, "The deductor's PAN should be shown.");
        $this->assertStringContainsString('ABCPV1234K', $html, "The employee's PAN should be shown.");
        $this->assertStringContainsString('Standard deduction', $html);
    }

    // ═══ Permissions ═════════════════════════════════════════════════════

    #[Test]
    public function the_hub_is_gated(): void
    {
        $outsider = Admin::create([
            'name' => 'Sales Person', 'email' => 'sales@docco.test',
            'password' => bcrypt('password'), 'phone' => '9990002222',
            'status' => 'active', 'business_id' => $this->business->id,
        ]);
        $outsider->assignRole('Sales');

        $this->actingAs($outsider, 'admin')->get(route('admin.documents.index'))->assertStatus(403);
        $this->actingAs($outsider, 'admin')
            ->get(route('admin.documents.render', ['key' => 'salary_register', 'month' => 8, 'year' => 2026]))
            ->assertStatus(403);
    }

    #[Test]
    public function a_pack_permission_only_opens_its_own_pack(): void
    {
        $accounts = Admin::create([
            'name' => 'Accounts Person', 'email' => 'accounts@docco.test',
            'password' => bcrypt('password'), 'phone' => '9990003333',
            'status' => 'active', 'business_id' => $this->business->id,
        ]);
        $accounts->assignRole('Accounts');

        $this->assertTrue($accounts->can('documents_sales.generate'));
        $this->assertFalse($accounts->can('documents_hr_letters.generate'));

        // Their own pack renders…
        $this->actingAs($accounts, 'admin')
            ->get(route('admin.documents.render', ['key' => 'sales_order', 'record' => $this->records['sales_order']]))
            ->assertStatus(200);

        // …someone else's does not.
        $this->actingAs($accounts, 'admin')
            ->get(route('admin.documents.render', ['key' => 'appointment_letter', 'record' => $this->employee->id]))
            ->assertStatus(403);
    }

    #[Test]
    public function the_letterhead_screen_needs_its_own_permission(): void
    {
        $hr = Admin::create([
            'name' => 'HR Person', 'email' => 'hr@docco.test',
            'password' => bcrypt('password'), 'phone' => '9990004444',
            'status' => 'active', 'business_id' => $this->business->id,
        ]);
        $hr->assignRole('HR Manager');

        // HR prints the people-facing packs but does not own the letterhead.
        $this->assertTrue($hr->can('documents_hr_letters.generate'));
        $this->assertFalse($hr->can('documents.configure'));

        $this->actingAs($hr, 'admin')->get(route('admin.documents.index'))->assertStatus(200);
        $this->actingAs($hr, 'admin')->get(route('admin.documents.letterhead'))->assertStatus(403);
    }

    #[Test]
    public function the_hub_renders_for_an_admin(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.documents.index'))
            ->assertStatus(200)
            ->assertSee('Documents')
            ->assertSee('Form 16')
            ->assertSee('Full &amp; Final Settlement', false);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.documents.index', ['document' => 'appointment_letter']))
            ->assertStatus(200)
            ->assertSee('Appointment Letter');

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.documents.letterhead'))
            ->assertStatus(200)
            ->assertSee('Letterhead Settings');
    }

    // ═══ Helper ══════════════════════════════════════════════════════════

    /**
     * Render one document and return the HTML DOMPDF was given.
     *
     * Content assertions run against the HTML rather than the compressed PDF
     * stream, which is not searchable.
     */
    private function renderHtml(string $key, array $params = []): string
    {
        $doc = DocumentCatalog::find($key);
        $this->assertNotNull($doc, "Unknown document {$key}.");

        $record = null;
        if (($doc['scope'] ?? '') === 'record') {
            $modelClass = app(DocumentDataResolver::class)->modelFor($doc['subject']);
            $record = $modelClass::find($params['record'] ?? null);
        }

        $data = app(DocumentDataResolver::class)->resolve($key, $record, $params);

        return view('pdf.documents.'.$doc['view'], array_merge($data, [
            'business' => $this->business->fresh(),
            'watermark' => $params['watermark'] ?? ($doc['watermark'] ?? null),
            'generatedAt' => now()->format('d M Y, h:i A'),
            'documentName' => $doc['name'],
            'documentKey' => $key,
        ]))->render();
    }
}
