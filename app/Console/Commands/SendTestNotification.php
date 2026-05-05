<?php

namespace App\Console\Commands;

use App\Mail\TransactionalNotification;
use App\Models\Business;
use App\Notifications\NotificationCatalog;
use App\Support\Tenancy\CurrentBusiness;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Send a single test notification email to a chosen address. Bypasses
 * the per-business toggle and recipient resolver — pure smoke-test.
 *
 *   php artisan notifications:test [email protected]
 *   php artisan notifications:test [email protected] --event=invoice.created
 *   php artisan notifications:test [email protected] --event=payslip.generated --business=1
 */
class SendTestNotification extends Command
{
    protected $signature = 'notifications:test
        {email : The email address to send the test to}
        {--event=invoice.created : Event key from NotificationCatalog}
        {--business= : Business id to use as context (default: first active)}';

    protected $description = 'Send a single test notification email synchronously to verify SMTP/templates';

    public function handle(): int
    {
        $email = $this->argument('email');
        $eventKey = $this->option('event');
        $businessId = $this->option('business');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error("Invalid email: {$email}");
            return self::FAILURE;
        }

        $event = NotificationCatalog::get($eventKey);
        if (! $event) {
            $this->error("Unknown event: {$eventKey}");
            $this->line('Run `php artisan notifications:list` to see available events.');
            return self::FAILURE;
        }

        $business = $businessId
            ? Business::find($businessId)
            : Business::where('is_active', true)->orderBy('id')->first();

        if (! $business) {
            $this->error('No business found.');
            return self::FAILURE;
        }

        $this->info("Sending '{$eventKey}' to {$email} as {$business->name}...");

        // Bind business so any model lookups (entity fixtures) resolve under
        // its scope.
        app(CurrentBusiness::class)->setWithoutSession($business);

        // Try to grab a real entity of the related type so the template has
        // something meaningful to render. If nothing exists, $entity is null
        // and the template uses defaults.
        $entity = $this->sampleEntity($event['related'] ?? null, $business);

        $mailable = new TransactionalNotification(
            eventKey: $eventKey,
            entity: $entity,
            business: $business,
            context: [
                'period' => 'May 2026',
                'invoice_number' => 'INV-TEST-0001',
                'order_number' => 'SO-TEST-0001',
                'new_status' => 'delivered',
                'old_status' => 'pending',
                'asset_code' => 'AST-TEST-0001',
                'asset_name' => 'Test Asset',
                'product_name' => 'Test Product',
                'current_stock' => 5,
                'days_overdue' => 7,
                'reason' => 'Sample reason for test',
                'comment_excerpt' => 'This is a sample comment.',
                'author' => 'System',
                'employees_count' => 25,
                'total_amount' => 500000,
            ],
            subject: '[TEST] '.($event['name'] ?? $eventKey),
            recipientName: 'Test Recipient',
            logId: null,
        );

        try {
            // Force truly-synchronous delivery. ->send() on a ShouldQueue
            // mailable normally queues it (and the test would silently
            // succeed even if SMTP is broken). ->sendNow() bypasses the queue.
            Mail::to($email)->sendNow($mailable);
            $this->info("✓ Sent successfully.");
            $this->line('  Driver: '.config('mail.default'));
            if (config('mail.default') === 'log') {
                $this->warn("  ⚠ MAIL_MAILER=log — the email was written to storage/logs/laravel.log, not actually delivered.");
                $this->warn("  Configure real SMTP in .env to deliver to Gmail/Outlook/etc.");
            }
            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('✗ Failed: '.$e->getMessage());
            $this->line('Driver: '.config('mail.default'));
            $this->line('Host:   '.config('mail.mailers.smtp.host'));
            $this->line('Port:   '.config('mail.mailers.smtp.port'));
            return self::FAILURE;
        }
    }

    protected function sampleEntity(?string $relatedType, $business)
    {
        if (! $relatedType) {
            return null;
        }
        $modelMap = [
            'invoice' => \App\Models\Invoice::class,
            'quotation' => \App\Models\Quotation::class,
            'proforma_invoice' => \App\Models\ProformaInvoice::class,
            'sales_order' => \App\Models\SalesOrder::class,
            'purchase_order' => \App\Models\PurchaseOrder::class,
            'goods_receipt' => \App\Models\GoodsReceipt::class,
            'payment' => \App\Models\Payment::class,
            'lead' => \App\Models\Lead::class,
            'product' => \App\Models\Product::class,
            'service_ticket' => \App\Models\ServiceTicket::class,
            'leave_request' => \App\Models\LeaveRequest::class,
            'payslip' => \App\Models\Payslip::class,
            'salary_structure' => \App\Models\SalaryStructure::class,
            'warning' => \App\Models\Warning::class,
            'penalty' => \App\Models\Penalty::class,
            'appraisal' => \App\Models\Appraisal::class,
            'department_feedback' => \App\Models\DepartmentFeedback::class,
            'holiday' => \App\Models\Holiday::class,
            'employee' => \App\Models\Employee::class,
            'asset_assignment' => \App\Models\AssetAssignment::class,
            'asset_maintenance_log' => \App\Models\AssetMaintenanceLog::class,
            'expense' => \App\Models\Expense::class,
        ];
        $class = $modelMap[$relatedType] ?? null;
        if (! $class) {
            return null;
        }

        return $class::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->latest()
            ->first();
    }
}
