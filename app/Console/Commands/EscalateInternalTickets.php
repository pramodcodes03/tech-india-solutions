<?php

namespace App\Console\Commands;

use App\Services\InternalTicketService;
use Illuminate\Console\Command;

/**
 * Escalate open internal helpdesk tickets that have breached their TAT or
 * crossed a configured escalation-level threshold. Runs hourly.
 */
class EscalateInternalTickets extends Command
{
    protected $signature = 'tickets:escalate-internal';

    protected $description = 'Escalate overdue internal helpdesk tickets per the escalation matrix.';

    public function handle(InternalTicketService $service): int
    {
        $count = $service->escalateBreaches();
        $this->info("Escalated {$count} internal ticket(s).");

        return self::SUCCESS;
    }
}
