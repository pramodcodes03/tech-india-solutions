<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MarkOverdueInvoices extends Command
{
    protected $signature = 'invoices:mark-overdue';

    protected $description = 'Mark invoices past due date as overdue';

    public function handle()
    {
        // withoutGlobalScopes: the scheduler has no active business, so the
        // tenant scope would otherwise fail-close and mark nothing. whereIn
        // groups the status check correctly — the previous where()->orWhere()
        // had wrong precedence (it matched ALL unpaid invoices regardless of
        // due date), and marking nothing overdue also stopped the overdue
        // reminder emails from ever firing.
        $count = Invoice::withoutGlobalScopes()
            ->whereIn('status', ['unpaid', 'partial'])
            ->where('due_date', '<', Carbon::today())
            ->update(['status' => 'overdue']);

        $this->info("Marked {$count} invoices as overdue.");

        return Command::SUCCESS;
    }
}
