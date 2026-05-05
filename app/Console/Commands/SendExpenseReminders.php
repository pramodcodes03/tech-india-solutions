<?php

namespace App\Console\Commands;

use App\Models\Expense;
use App\Notifications\NotificationDispatcher;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Walks every unpaid expense due within 3 days (or already past due) and
 * fires the appropriate reminder event through NotificationDispatcher:
 *   T-3  → expense.reminder_t3
 *   T-1  → expense.reminder_t1
 *   due  → expense.due_today
 *   +N   → expense.overdue   (daily until paid)
 *
 * Idempotent within a day: the expense's last_reminder_stage + last_reminder_sent_at
 * columns prevent the same stage firing twice on the same date.
 */
class SendExpenseReminders extends Command
{
    protected $signature = 'expenses:send-reminders {--dry-run}';

    protected $description = 'Fire reminder events for unpaid expenses approaching or past their due date';

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

        $count = 0;

        foreach ($expenses as $expense) {
            if (! $expense->business || ! $expense->business->is_active) {
                continue;
            }

            $daysFromDue = (int) ($today->diffInDays($expense->due_date, false) * -1);

            [$stage, $eventKey] = match (true) {
                $daysFromDue === -3 => ['t-3', 'expense.reminder_t3'],
                $daysFromDue === -1 => ['t-1', 'expense.reminder_t1'],
                $daysFromDue === 0  => ['due', 'expense.due_today'],
                $daysFromDue > 0    => ['overdue', 'expense.overdue'],
                default => [null, null],
            };

            if (! $stage) {
                continue;
            }

            // De-dupe within a day.
            if (
                $expense->last_reminder_stage === $stage
                && $expense->last_reminder_sent_at
                && $expense->last_reminder_sent_at->isSameDay($today)
            ) {
                continue;
            }

            $this->line("  → {$eventKey} for {$expense->expense_code} ({$expense->business->name})");

            if (! $dryRun) {
                NotificationDispatcher::fire($eventKey, $expense, [
                    'days_overdue' => max(0, $daysFromDue),
                    'category' => $expense->category->name ?? null,
                ]);

                $expense->update([
                    'last_reminder_stage' => $stage,
                    'last_reminder_sent_at' => now(),
                ]);
            }

            $count++;
        }

        $this->info(($dryRun ? '[DRY RUN] Would fire' : 'Fired')." {$count} reminder event(s).");

        return self::SUCCESS;
    }
}
