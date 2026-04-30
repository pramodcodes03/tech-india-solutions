<?php

namespace App\Console\Commands;

use App\Mail\ExpenseDueReminder;
use App\Models\Admin;
use App\Models\Expense;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

/**
 * Sends T-3 / T-1 / due / overdue reminder emails for unpaid expenses.
 *
 * Recipients: every active admin of the expense's business.
 *
 * Cadence:
 *   - 3 days before due date: 't-3' email (once)
 *   - 1 day before:           't-1' email (once)
 *   - On due date:            'due' email (once)
 *   - Each day overdue:       'overdue' email (once per day)
 *
 * The expense's last_reminder_stage + last_reminder_sent_at columns
 * deduplicate so we don't send the same stage twice.
 *
 * Stops automatically when status flips to 'paid' or 'cancelled'.
 */
class SendExpenseReminders extends Command
{
    protected $signature = 'expenses:send-reminders {--dry-run : Print what would be sent without actually sending}';

    protected $description = 'Send T-3 / T-1 / due / overdue reminder emails for unpaid expenses';

    public function handle(): int
    {
        $today = Carbon::today();
        $dryRun = $this->option('dry-run');

        $expenses = Expense::withoutGlobalScopes()
            ->where('status', Expense::STATUS_UNPAID)
            ->whereNotNull('due_date')
            ->whereNull('deleted_at')
            ->whereDate('due_date', '<=', $today->copy()->addDays(3))
            ->with('business')
            ->cursor();

        $sent = 0;

        foreach ($expenses as $expense) {
            if (! $expense->business || ! $expense->business->is_active) {
                continue;
            }

            // diffInDays(false) returns positive when due_date is in the future,
            // negative when past. Flip the sign so:
            //   -3 = 3 days before, 0 = today, +N = N days overdue.
            // Cast to int — Carbon returns float, which breaks strict === comparisons below.
            $daysFromDue = (int) ($today->diffInDays($expense->due_date, false) * -1);

            $stage = match (true) {
                $daysFromDue === -3 => 't-3',
                $daysFromDue === -1 => 't-1',
                $daysFromDue === 0  => 'due',
                $daysFromDue > 0    => 'overdue',
                default => null, // -2 or other gaps: don't send
            };

            if ($stage === null) {
                continue;
            }

            // De-dupe: skip if we already sent THIS stage on the same day.
            $alreadySentToday =
                $expense->last_reminder_stage === $stage
                && $expense->last_reminder_sent_at
                && $expense->last_reminder_sent_at->isSameDay($today);

            if ($alreadySentToday) {
                continue;
            }

            // For overdue, also skip if we already sent overdue today (to support
            // running the command multiple times per day safely).
            if ($stage === 'overdue' && $alreadySentToday) {
                continue;
            }

            $admins = Admin::where('business_id', $expense->business_id)
                ->where('status', 'active')
                ->whereNotNull('email')
                ->get();

            if ($admins->isEmpty()) {
                $this->warn("  - No active admins for business #{$expense->business_id}, skipping {$expense->expense_code}");
                continue;
            }

            $this->line("  → {$stage} for {$expense->expense_code} ({$expense->business->name}) → ".$admins->pluck('email')->implode(', '));

            if (! $dryRun) {
                foreach ($admins as $admin) {
                    try {
                        Mail::to($admin->email)->send(
                            new ExpenseDueReminder($expense, $stage, $daysFromDue),
                        );
                    } catch (\Throwable $e) {
                        $this->error("  ✗ Failed sending to {$admin->email}: ".$e->getMessage());
                    }
                }

                $expense->update([
                    'last_reminder_stage' => $stage,
                    'last_reminder_sent_at' => now(),
                ]);
            }

            $sent++;
        }

        $this->info(($dryRun ? '[DRY RUN] Would send' : 'Sent')." reminders for {$sent} expense(s).");

        return self::SUCCESS;
    }
}
