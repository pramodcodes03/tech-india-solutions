<?php

namespace App\Mail;

use App\Models\Business;
use App\Models\NotificationLog;
use App\Notifications\NotificationCatalog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Single Mailable that drives every event in the catalog.
 *
 * - Picks the body view at runtime: emails.events.{event_key_with_dots_as_dashes}
 * - Attaches a PDF if the catalog event defines one
 * - Marks its NotificationLog row as 'sent' or 'failed' on send
 *
 * This means adding a new notification = creating one .blade.php file + one
 * catalog entry. No new Mailable class per event.
 */
class TransactionalNotification extends BaseBusinessMailable implements ShouldQueue
{
    public ?Model $entity;
    public string $eventKey;
    public array $context;
    public string $subjectLine;
    public ?string $recipientName;
    public ?int $logId;

    public function __construct(
        string $eventKey,
        ?Model $entity,
        Business $business,
        array $context,
        string $subject,
        ?string $recipientName,
        ?int $logId = null,
    ) {
        parent::__construct($business);
        $this->eventKey = $eventKey;
        $this->entity = $entity;
        $this->context = $context;
        $this->subjectLine = $subject;
        $this->recipientName = $recipientName;
        $this->logId = $logId;
    }

    public function envelope(): Envelope
    {
        $from = $this->defaultFrom();
        $replyTo = $this->defaultReplyTo();

        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address($from['address'], $from['name']),
            replyTo: $replyTo
                ? [new \Illuminate\Mail\Mailables\Address($replyTo['address'], $replyTo['name'])]
                : [],
            subject: $this->subjectLine,
        );
    }

    public function content(): Content
    {
        // Convert event key to view name: invoice.created → emails.events.invoice_created
        // Fall back to the shared _generic template if the specific one isn't authored yet.
        $specific = 'emails.events.'.str_replace('.', '_', $this->eventKey);
        $viewName = view()->exists($specific) ? $specific : 'emails.events._generic';

        return new Content(
            view: $viewName,
            with: [
                'business' => $this->business,
                'entity' => $this->entity,
                'context' => $this->context,
                'recipientName' => $this->recipientName,
                'eventKey' => $this->eventKey,
                'subject' => $this->subjectLine,
            ],
        );
    }

    public function attachments(): array
    {
        $event = NotificationCatalog::get($this->eventKey);
        if (! $event || empty($event['pdf'])) {
            return [];
        }

        $viewName = $event['pdf']['view'];
        $fileNameTemplate = $event['pdf']['name'];

        try {
            // 1. Reload the entity with ALL relations the PDF template uses.
            //    Required because this mailable is queued (ShouldQueue): the
            //    queue worker deserialises the entity and only the columns +
            //    the relations explicitly re-loaded survive. Without this,
            //    invoice items / payments / customer details all render blank
            //    because the lazy-load attempts return nothing.
            $entity = $this->reloadEntityWithRelations($this->entity);

            // 2. Pull settings from DB. The hardcoded `[]` here previously
            //    meant bank details / terms / company defaults rendered blank.
            $settings = \App\Models\Setting::pluck('value', 'key')->toArray();

            // 3. Tenant identity from the entity's own business when available,
            //    so a queue worker without an active session still gets the
            //    right header instead of falling back to a generic default.
            $business = ($entity && method_exists($entity, 'business') && $entity->business)
                ? $entity->business
                : $this->business;

            // 4. Pre-compute totals that quotation/proforma templates expect.
            $totals = $this->computePdfTotals($entity);

            $pdf = Pdf::loadView($viewName, array_merge([
                $event['related'] ?? 'entity' => $entity,
                'entity'         => $entity,
                'business'       => $business,
                'context'        => $this->context,
                'settings'       => $settings,
                // Some PDFs expect a fixed variable name (e.g. "invoice"),
                // others use $entity. Pass both for safety.
                'invoice'        => $entity,
                'quotation'      => $entity,
                'purchase_order' => $entity,
                'po'             => $entity,
                'proforma'       => $entity,
                'payslip'        => $entity,
                'appraisal'      => $entity,
                'expense'        => $entity,
            ], $totals));

            $fileName = $this->renderFileName($fileNameTemplate);
            $tmpPath = tempnam(sys_get_temp_dir(), 'mail-pdf-').'.pdf';
            file_put_contents($tmpPath, $pdf->output());

            return [
                \Illuminate\Mail\Mailables\Attachment::fromPath($tmpPath)
                    ->as($fileName)
                    ->withMime('application/pdf'),
            ];
        } catch (Throwable $e) {
            Log::error("[Notifications] PDF attachment failed for {$this->eventKey}: ".$e->getMessage());
            return [];
        }
    }

    /**
     * Reload the entity from the DB with the relations its PDF template
     * needs. Without this, the emailed PDF shows headers + business info
     * but blank line-items / customer / payments — because the queued job
     * lost the relations that the controller's on-demand path eager-loads.
     *
     * Falls back to the original entity if class/relation lookup fails.
     */
    protected function reloadEntityWithRelations(?Model $entity): ?Model
    {
        if (! $entity || ! method_exists($entity, 'getKey')) {
            return $entity;
        }

        // Map model class → relations needed by its PDF template. Keep in
        // sync with each controller's pdf($id) method's `with([...])` call.
        $relationsByClass = [
            \App\Models\Invoice::class          => ['customer', 'items.product', 'creator', 'payments', 'business'],
            \App\Models\Quotation::class        => ['customer', 'items.product', 'creator', 'business'],
            \App\Models\ProformaInvoice::class  => ['customer', 'items.product', 'creator', 'business'],
            \App\Models\PurchaseOrder::class    => ['vendor', 'items.product', 'creator', 'business'],
            \App\Models\Payslip::class          => ['employee.department', 'employee.designation', 'employee.business', 'penalties.penaltyType'],
            \App\Models\Expense::class          => ['category', 'subcategory', 'creator', 'paidByAdmin', 'business'],
            \App\Models\Appraisal::class        => ['employee', 'department', 'designation', 'business'],
        ];

        $class = get_class($entity);
        $relations = $relationsByClass[$class] ?? [];

        try {
            $fresh = $class::withoutGlobalScopes()
                ->with($relations)
                ->find($entity->getKey());
            return $fresh ?: $entity;
        } catch (Throwable) {
            return $entity;
        }
    }

    protected function renderFileName(string $template): string
    {
        return preg_replace_callback('/\{([a-z_.]+)\}/i', function ($m) {
            $path = explode('.', $m[1]);
            $root = array_shift($path);
            $source = match ($root) {
                'entity' => $this->entity,
                'business' => $this->business,
                'context' => $this->context,
                default => null,
            };
            foreach ($path as $seg) {
                if ($source === null) {
                    return '';
                }
                $source = is_array($source) ? ($source[$seg] ?? null) : ($source->{$seg} ?? null);
            }
            return (string) ($source ?? '');
        }, $template);
    }

    /**
     * Compute totals expected by quotation/proforma PDF templates when the
     * entity is one of those types. Returns empty array otherwise.
     */
    protected function computePdfTotals(?\Illuminate\Database\Eloquent\Model $entity): array
    {
        if (! $entity || empty($entity->items)) {
            return [];
        }
        $items = $entity->items;
        if (! is_iterable($items)) {
            return [];
        }

        $subtotal = 0.0;
        foreach ($items as $item) {
            $qty = (float) ($item->quantity ?? 0);
            $rate = (float) ($item->unit_price ?? $item->rate ?? 0);
            $disc = (float) ($item->discount_value ?? 0);
            $taxPct = (float) ($item->tax_percent ?? 0);
            $base = max(0, $qty * $rate - $disc);
            $subtotal += $base + $base * ($taxPct / 100);
        }
        $subtotal = round($subtotal, 2);

        $discVal = (float) ($entity->discount_value ?? 0);
        $discAmt = ($entity->discount_type ?? null) === 'percent'
            ? round($subtotal * $discVal / 100, 2)
            : round($discVal, 2);
        $afterDisc = $subtotal - $discAmt;
        $taxAmt = round($afterDisc * ((float) ($entity->tax_percent ?? 0) / 100), 2);
        $grandTotal = round($afterDisc + $taxAmt, 2);

        return [
            'pdfSubtotal' => $subtotal,
            'pdfDiscVal' => $discVal,
            'pdfDiscAmt' => $discAmt,
            'pdfTaxAmt' => $taxAmt,
            'pdfGrandTotal' => $grandTotal,
        ];
    }

    public function headers(): \Illuminate\Mail\Mailables\Headers
    {
        // Stamp the log row id into a custom header so the global MessageSent
        // listener can mark this row as 'sent' once delivery succeeds.
        return new \Illuminate\Mail\Mailables\Headers(
            text: $this->logId ? ['X-Notification-Log-Id' => (string) $this->logId] : [],
        );
    }
}
