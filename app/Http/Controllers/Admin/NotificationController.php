<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\TransactionalNotification;
use App\Models\NotificationLog;
use App\Models\NotificationSetting;
use App\Notifications\NotificationCatalog;
use App\Support\Tenancy\CurrentBusiness;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class NotificationController extends Controller
{
    public function index()
    {
        abort_unless(Auth::guard('admin')->user()->can('settings.view'), 403);

        $business = app(CurrentBusiness::class)->get();
        abort_unless($business, 400, 'No active business.');

        $catalog = NotificationCatalog::byModule();
        $settings = NotificationSetting::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->get()
            ->keyBy('event_key');

        return view('admin.notifications.index', compact('catalog', 'settings', 'business'));
    }

    public function update(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('settings.edit'), 403);

        $business = app(CurrentBusiness::class)->get();
        abort_unless($business, 400);

        $payload = $request->validate([
            'events' => ['array'],
            'events.*.is_enabled' => ['nullable', 'boolean'],
            'events.*.extra_recipients' => ['nullable', 'string', 'max:1000'],
        ]);

        foreach ($payload['events'] ?? [] as $key => $values) {
            if (! NotificationCatalog::exists($key)) {
                continue;
            }

            $extras = collect(explode(',', $values['extra_recipients'] ?? ''))
                ->map(fn ($e) => trim($e))
                ->filter(fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL))
                ->values()
                ->all();

            NotificationSetting::withoutGlobalScopes()->updateOrCreate(
                ['business_id' => $business->id, 'event_key' => $key],
                [
                    'is_enabled' => (bool) ($values['is_enabled'] ?? false),
                    'extra_recipients' => $extras,
                    'updated_by' => Auth::guard('admin')->id(),
                ],
            );
        }

        return back()->with('success', 'Notification settings updated.');
    }

    /**
     * Send a test email of any catalog event to the logged-in admin.
     */
    public function test(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('settings.edit'), 403);

        $data = $request->validate([
            'event_key' => ['required', 'string'],
            'recipient_email' => ['nullable', 'email'],
            'recipient_name' => ['nullable', 'string', 'max:120'],
        ]);

        $event = NotificationCatalog::get($data['event_key']);
        abort_unless($event, 404);

        $business = app(CurrentBusiness::class)->get();
        $admin = Auth::guard('admin')->user();

        // Recipient: explicit override → otherwise fall back to logged-in admin.
        $toEmail = $data['recipient_email'] ?? $admin->email;
        $toName = $data['recipient_name'] ?? $admin->name;

        // Build fake context — find a real entity of the related type if possible
        // so the template renders something meaningful.
        $entity = $this->sampleEntity($event['related'] ?? null, $business);

        $mail = new TransactionalNotification(
            eventKey: $data['event_key'],
            entity: $entity,
            business: $business,
            context: [
                'period' => 'Sample Period',
                'invoice_number' => 'INV-TEST-0001',
                'order_number' => 'SO-TEST-0001',
                'new_status' => 'Confirmed',
                'old_status' => 'Pending',
                'asset_code' => 'AST-TEST-0001',
                'asset_name' => 'Test Asset',
                'product_name' => 'Test Product',
                'current_stock' => 5,
                'days_overdue' => 7,
                'comment_excerpt' => 'Sample comment for testing.',
                'author' => 'System',
            ],
            subject: '[TEST] '.$event['name'],
            recipientName: $toName,
            logId: null,
        );

        try {
            // Force truly-synchronous delivery. ->send() on a ShouldQueue
            // mailable would just queue it (and a stopped queue worker would
            // make this silently never deliver while still showing "sent").
            // ->sendNow() bypasses the queue and surfaces SMTP errors here.
            Mail::to($toEmail, $toName)->sendNow($mail);

            $note = config('mail.default') === 'log'
                ? ' (driver=log — written to storage/logs/laravel.log, not actually delivered. Configure SMTP in .env to send for real.)'
                : '';

            return back()->with('success', "Test email sent to {$toEmail}.{$note}");
        } catch (\Throwable $e) {
            return back()->with('error', 'Test email failed: '.$e->getMessage());
        }
    }

    public function logs(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('settings.view'), 403);

        $business = app(CurrentBusiness::class)->get();

        $logs = NotificationLog::query()
            ->when($business, fn ($q) => $q->where('business_id', $business->id))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->event, fn ($q, $e) => $q->where('event_key', $e))
            ->when($request->search, fn ($q, $s) => $q->where(function ($q2) use ($s) {
                $q2->where('recipient_email', 'like', "%{$s}%")
                   ->orWhere('subject', 'like', "%{$s}%");
            }))
            ->latest()
            ->paginate(50)
            ->withQueryString();

        $events = collect(NotificationCatalog::events())
            ->map(fn ($e, $k) => ['key' => $k, 'name' => $e['name']])
            ->values();

        return view('admin.notifications.logs', compact('logs', 'events'));
    }

    /**
     * Try to find a real entity of the given type for sample emails.
     */
    protected function sampleEntity(?string $relatedType, $business)
    {
        if (! $relatedType || ! $business) {
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
            'stock_movement' => \App\Models\StockMovement::class,
            'service_ticket' => \App\Models\ServiceTicket::class,
            'leave_request' => \App\Models\LeaveRequest::class,
            'leave_balance' => \App\Models\LeaveBalance::class,
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
