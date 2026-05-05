<?php

namespace App\Notifications;

use App\Mail\TransactionalNotification;
use App\Models\Business;
use App\Models\NotificationLog;
use App\Models\NotificationSetting;
use App\Support\Tenancy\CurrentBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Single entry point for firing transactional notifications.
 *
 * Usage:
 *   NotificationDispatcher::fire('invoice.created', $invoice);
 *   NotificationDispatcher::fire('payment.received', $payment, ['invoice_number' => $invoice->invoice_number]);
 *
 * What it does:
 *   1. Looks up the event in NotificationCatalog
 *   2. Checks the per-business toggle (notification_settings.is_enabled)
 *   3. Resolves recipients via RecipientResolver
 *   4. Queues a TransactionalNotification mailable for each recipient
 *   5. Logs each send to notification_logs
 *
 * Failures during recipient resolution / dispatch are caught and logged as
 * 'failed' rows so the admin UI can surface them. Never throws to the caller.
 */
class NotificationDispatcher
{
    public function __construct(
        protected RecipientResolver $resolver,
    ) {}

    public static function fire(string $eventKey, ?Model $entity = null, array $context = []): void
    {
        app(self::class)->dispatch($eventKey, $entity, $context);
    }

    public function dispatch(string $eventKey, ?Model $entity, array $context = []): void
    {
        try {
            $event = NotificationCatalog::get($eventKey);
            if (! $event) {
                Log::warning("[Notifications] Unknown event key: {$eventKey}");
                return;
            }

            $business = $this->resolveBusiness($entity);
            if (! $business) {
                Log::warning("[Notifications] {$eventKey}: no business resolvable from entity");
                return;
            }

            // Toggle check — default ON unless settings row says otherwise.
            $defaultOn = $event['default_on'] ?? true;
            $setting = NotificationSetting::withoutGlobalScopes()
                ->where('business_id', $business->id)
                ->where('event_key', $eventKey)
                ->first();

            $isEnabled = $setting ? (bool) $setting->is_enabled : $defaultOn;
            if (! $isEnabled) {
                return;
            }

            $extraRecipients = $setting?->extra_recipients ?? [];

            // Resolve catalog-defined recipients.
            $recipients = $this->resolver->resolve(
                $event['recipients'] ?? [],
                $entity,
                $business,
                $context,
            );

            // Tack on extra_recipients (raw email strings).
            foreach ($extraRecipients as $extraEmail) {
                if (! empty($extraEmail) && filter_var($extraEmail, FILTER_VALIDATE_EMAIL)) {
                    $recipients->push(['email' => $extraEmail, 'name' => null]);
                }
            }

            $recipients = $recipients
                ->unique(fn ($r) => strtolower($r['email']))
                ->values();

            if ($recipients->isEmpty()) {
                $this->log($business, $eventKey, '(no recipients)', null, $entity, 'failed', null, 'No recipients resolved');
                return;
            }

            // Build subject by interpolating placeholders.
            $subject = $this->renderTemplate(
                $event['subject'] ?? $eventKey,
                $entity,
                $business,
                $context,
            );

            // Deep-link target for the in-app bell. Resolved once per event
            // because all admin recipients share the same target URL.
            $link = $this->resolveLink($eventKey, $entity, $business);

            foreach ($recipients as $r) {
                $logId = $this->log($business, $eventKey, $subject, $r, $entity, 'queued')->id;

                // If this recipient email matches an Admin record, also drop
                // an in-app inbox notification so they see a bell icon badge.
                $this->createInboxNotification($r, $business, $eventKey, $subject, $entity, $link);

                $mail = new TransactionalNotification(
                    eventKey: $eventKey,
                    entity: $entity,
                    business: $business,
                    context: $context,
                    subject: $subject,
                    recipientName: $r['name'],
                    logId: $logId,
                );

                try {
                    Mail::to($r['email'], $r['name'] ?? null)->queue($mail);
                } catch (Throwable $e) {
                    NotificationLog::where('id', $logId)->update([
                        'status' => 'failed',
                        'error' => substr($e->getMessage(), 0, 1000),
                    ]);
                    Log::error("[Notifications] Queue failed for {$eventKey} → {$r['email']}: ".$e->getMessage());
                }
            }
        } catch (Throwable $e) {
            // Never let a notification crash the request.
            Log::error("[Notifications] Dispatch crashed for {$eventKey}: ".$e->getMessage());
        }
    }

    protected function resolveBusiness(?Model $entity): ?Business
    {
        // Entity carries business_id (the typical case).
        if ($entity && ! empty($entity->business_id)) {
            return Business::find($entity->business_id);
        }

        // Fallback to current request's active business.
        return app(CurrentBusiness::class)->get();
    }

    protected function renderTemplate(string $template, ?Model $entity, Business $business, array $context): string
    {
        return preg_replace_callback('/\{([a-z_.]+)\}/i', function ($m) use ($entity, $business, $context) {
            $path = $m[1];
            return $this->lookupValue($path, $entity, $business, $context);
        }, $template);
    }

    protected function lookupValue(string $path, ?Model $entity, Business $business, array $context): string
    {
        $segments = explode('.', $path);
        $root = array_shift($segments);

        $source = match ($root) {
            'entity' => $entity,
            'business' => $business,
            'context' => $context,
            default => null,
        };

        foreach ($segments as $seg) {
            if ($source === null) {
                return '';
            }
            if (is_array($source)) {
                $source = $source[$seg] ?? null;
            } elseif (is_object($source)) {
                $source = $source->{$seg} ?? null;
            } else {
                return '';
            }
        }

        if ($source instanceof \Illuminate\Database\Eloquent\Model) {
            return (string) ($source->name ?? $source->id);
        }
        return $source === null ? '' : (string) $source;
    }

    protected function log(
        Business $business,
        string $eventKey,
        string $subject,
        ?array $recipient,
        ?Model $entity,
        string $status,
        ?\DateTimeInterface $sentAt = null,
        ?string $error = null,
    ): NotificationLog {
        return NotificationLog::create([
            'business_id' => $business->id,
            'event_key' => $eventKey,
            'subject' => substr($subject, 0, 250),
            'recipient_email' => $recipient['email'] ?? '(none)',
            'recipient_name' => $recipient['name'] ?? null,
            'related_type' => $entity?->getMorphClass(),
            'related_id' => $entity?->getKey(),
            'status' => $status,
            'error' => $error,
            'sent_at' => $sentAt,
        ]);
    }

    /**
     * Create an in-app inbox row for any recipient whose email matches an
     * Admin record. Customers, vendors and employees still receive their
     * email but DO NOT show up in the admin bell. We match super admins
     * (business_id=null) too so they see notifications for any business
     * they were addressed in.
     */
    protected function createInboxNotification(
        array $recipient,
        Business $business,
        string $eventKey,
        string $subject,
        ?Model $entity,
        ?string $link,
    ): void {
        $email = strtolower($recipient['email'] ?? '');
        if (! $email) {
            return;
        }

        // Match by email; admin can be in this business OR a super admin.
        $admin = \App\Models\Admin::whereRaw('LOWER(email) = ?', [$email])
            ->where(function ($q) use ($business) {
                $q->where('business_id', $business->id)->orWhereNull('business_id');
            })
            ->first();

        if (! $admin) {
            return;
        }

        try {
            \App\Models\AdminNotification::create([
                'business_id' => $business->id,
                'admin_id' => $admin->id,
                'event_key' => $eventKey,
                'title' => substr($subject, 0, 250),
                'body' => null,
                'link' => $link,
                'related_type' => $entity?->getMorphClass(),
                'related_id' => $entity?->getKey(),
            ]);
        } catch (Throwable $e) {
            \Illuminate\Support\Facades\Log::warning(
                "[Notifications] Inbox row create failed for admin {$admin->id} / {$eventKey}: ".$e->getMessage()
            );
        }
    }

    /**
     * Resolve the deep-link URL the bell-click takes the user to.
     * Returns null if the event has no obvious admin landing page.
     */
    protected function resolveLink(string $eventKey, ?Model $entity, Business $business): ?string
    {
        // Helper: safely build a route URL, swallow exceptions if the route
        // or required params are missing (defensive — never fail dispatch).
        $route = function (string $name, $params = []) {
            try {
                return route($name, $params);
            } catch (Throwable $e) {
                return null;
            }
        };

        // Approval/queue events go straight to the queue page.
        $queueRoutes = [
            'salary_structure.submitted' => 'admin.hr.payroll.approvals.index',
            'salary_structure.approved' => 'admin.hr.payroll.approvals.index',
            'salary_structure.rejected' => 'admin.hr.payroll.approvals.index',
            'bank_edit.requested' => 'admin.hr.bank-edit-requests.index',
            'bank_edit.approved' => 'admin.hr.bank-edit-requests.index',
            'bank_edit.rejected' => 'admin.hr.bank-edit-requests.index',
        ];
        if (isset($queueRoutes[$eventKey])) {
            return $route($queueRoutes[$eventKey]);
        }

        // Entity-show events: deep-link to the record itself.
        $entityShowRoutes = [
            'invoice.created' => ['admin.invoices.show', $entity?->id],
            'invoice.cancelled' => ['admin.invoices.show', $entity?->id],
            'invoice.reminder_t3' => ['admin.invoices.show', $entity?->id],
            'invoice.overdue' => ['admin.invoices.show', $entity?->id],
            'payment.received' => ['admin.payments.show', $entity?->id],
            'quotation.sent' => ['admin.quotations.show', $entity?->id],
            'quotation.approved' => ['admin.quotations.show', $entity?->id],
            'quotation.rejected' => ['admin.quotations.show', $entity?->id],
            'quotation.converted_to_so' => ['admin.quotations.show', $entity?->id],
            'proforma.issued' => ['admin.proforma-invoices.show', $entity?->id],
            'sales_order.status_changed' => ['admin.sales-orders.show', $entity?->id],
            'purchase_order.issued' => ['admin.purchase-orders.show', $entity?->id],
            'goods_receipt.received' => ['admin.purchase-orders.show', $entity?->purchase_order_id ?? null],
            'lead.assigned' => ['admin.leads.show', $entity?->id],
            'lead.status_changed' => ['admin.leads.show', $entity?->id],
            'lead.converted' => ['admin.leads.show', $entity?->id],
            'service_ticket.created' => ['admin.service-tickets.show', $entity?->id],
            'service_ticket.assigned' => ['admin.service-tickets.show', $entity?->id],
            'service_ticket.commented' => ['admin.service-tickets.show', $entity?->id],
            'service_ticket.status_changed' => ['admin.service-tickets.show', $entity?->id],
            'leave.applied' => ['admin.hr.leaves.index', null],
            'leave.approved' => ['admin.hr.leaves.index', null],
            'leave.rejected' => ['admin.hr.leaves.index', null],
            'leave.cancelled' => ['admin.hr.leaves.index', null],
            'feedback.submitted' => ['admin.hr.feedback.show', $entity?->id],
            'warning.issued' => ['admin.hr.warnings.show', $entity?->id],
            'warning.withdrawn' => ['admin.hr.warnings.show', $entity?->id],
            'penalty.issued' => ['admin.hr.penalties.index', null],
            'penalty.reduced' => ['admin.hr.penalties.index', null],
            'appraisal.recorded' => ['admin.hr.appraisals.show', $entity?->id],
            'expense.reminder_t3' => ['admin.expenses.show', $entity?->id],
            'expense.reminder_t1' => ['admin.expenses.show', $entity?->id],
            'expense.due_today' => ['admin.expenses.show', $entity?->id],
            'expense.overdue' => ['admin.expenses.show', $entity?->id],
            'stock.low' => ['admin.products.show', $entity?->id],
        ];

        if (isset($entityShowRoutes[$eventKey])) {
            [$name, $param] = $entityShowRoutes[$eventKey];
            if ($param !== null) {
                return $route($name, $param);
            }
            return $route($name);
        }

        return null;
    }
}
