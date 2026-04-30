<?php

namespace App\Console\Commands;

use App\Models\Expense;
use App\Services\ExpenseService;
use Illuminate\Console\Command;

/**
 * Walks every recurring expense template (the original row, with no
 * recurring_template_id of its own) and generates the next monthly
 * instance if one isn't already there for the upcoming due date.
 *
 * Designed to run once a day from app/Console/Kernel.php.
 * Idempotent: running it twice on the same day is a no-op.
 */
class GenerateRecurringExpenses extends Command
{
    protected $signature = 'expenses:generate-recurring';

    protected $description = 'Auto-generate the next month\'s row for each recurring expense template';

    public function handle(ExpenseService $service): int
    {
        // Skip the global business scope — we walk every business at once.
        $templates = Expense::withoutGlobalScopes()
            ->where('type', Expense::TYPE_RECURRING)
            ->whereNull('recurring_template_id')
            ->whereNull('deleted_at')
            ->cursor();

        $created = 0;

        foreach ($templates as $template) {
            $instance = $service->generateNextRecurring($template);
            if ($instance) {
                $created++;
                $this->line("  ✓ {$template->business_id}: spawned {$instance->expense_code} due {$instance->due_date->toDateString()} from {$template->title}");
            }
        }

        $this->info("Generated {$created} recurring expense instance(s).");

        return self::SUCCESS;
    }
}
