<?php

namespace App\Console\Commands;

use App\Services\AttendanceRegularizationService;
use Illuminate\Console\Command;

/**
 * Flag attendance-correction requests that have stayed open past their
 * configurable TAT (default 48h). Escalated rows light up red in the HR
 * queue and fire an escalation notification.
 */
class EscalateAttendanceRegularizations extends Command
{
    protected $signature = 'attendance:escalate-regularizations';

    protected $description = 'Escalate attendance regularization requests past their resolution TAT.';

    public function handle(AttendanceRegularizationService $service): int
    {
        $count = $service->escalateBreaches();
        $this->info("Escalated {$count} breaching regularization request(s).");

        return self::SUCCESS;
    }
}
