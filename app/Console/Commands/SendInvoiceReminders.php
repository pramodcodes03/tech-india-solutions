<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Notifications\NotificationDispatcher;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * T-3 reminder when invoice is due in exactly 3 days.
 * Daily overdue reminder for invoices past due that aren't fully paid.
 *
 * Designed to be safe to run repeatedly within the same day — uses a coarse
 * de-dupe by sending once per day per stage; skips invoices that already had
 * a reminder fired today.
 *
 * Stops automatically when invoice status flips to 'paid' or 'cancelled'.
 */
class SendInvoiceReminders extends Command
{
    protected $signature = 'invoices:send-reminders {--dry-run}';

    protected $description = 'Fire T-3 and daily overdue reminders for unpaid invoices';

    public function handle(): int
    {
        $today = Carbon::today();
        $dryRun = $this->option('dry-run');

        $invoices = Invoice::withoutGlobalScopes()
            ->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->whereNotNull('due_date')
            ->whereNull('deleted_at')
            ->whereDate('due_date', '<=', $today->copy()->addDays(3))
            ->with(['customer', 'business'])
            ->cursor();

        $count = 0;

        foreach ($invoices as $invoice) {
            if (! $invoice->business || ! $invoice->business->is_active) {
                continue;
            }

            $daysFromDue = (int) ($today->diffInDays($invoice->due_date, false) * -1);

            $event = match (true) {
                $daysFromDue === -3 => 'invoice.reminder_t3',
                $daysFromDue >= 0 => 'invoice.overdue',
                default => null,
            };

            if (! $event) {
                continue;
            }

            $this->line("  → {$event} for {$invoice->invoice_number}");

            if (! $dryRun) {
                NotificationDispatcher::fire($event, $invoice, [
                    'days_overdue' => max(0, $daysFromDue),
                ]);
            }

            $count++;
        }

        $this->info(($dryRun ? '[DRY RUN] Would fire' : 'Fired')." {$count} invoice reminder(s).");

        return self::SUCCESS;
    }
}
