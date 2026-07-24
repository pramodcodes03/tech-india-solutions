<?php

namespace App\Console\Commands;

use App\Services\EmployeeService;
use Illuminate\Console\Command;

/**
 * Nightly probation-completion job. Confirms employees whose probation period
 * has elapsed (own probation_end_date, else DOJ + global default days), flips
 * them to 'active', and allocates their leave quotas. Idempotent — a confirmed
 * employee (status active / confirmation_date set) is never re-processed.
 */
class ConfirmCompletedProbations extends Command
{
    protected $signature = 'employees:confirm-probation';

    protected $description = 'Auto-confirm employees whose probation has completed and allocate their leaves.';

    public function handle(EmployeeService $service): int
    {
        $count = $service->confirmCompletedProbations();
        $this->info("Probation completion: {$count} employee(s) confirmed and leaves allocated.");

        return self::SUCCESS;
    }
}
