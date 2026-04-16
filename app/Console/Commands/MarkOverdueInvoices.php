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
        $count = Invoice::where('status', 'unpaid')
            ->orWhere('status', 'partial')
            ->where('due_date', '<', Carbon::today())
            ->update(['status' => 'overdue']);

        $this->info("Marked {$count} invoices as overdue.");

        return Command::SUCCESS;
    }
}
