<?php

namespace App\Console\Commands;

use App\Services\LeaveAccrualService;
use Illuminate\Console\Command;

/**
 * Daily leave accrual. Idempotent — only credits an (employee, type, period)
 * combination once, so running every day is safe; the actual credit happens
 * the first day of each accrual period (month / half-year / year start).
 */
class AccrueLeave extends Command
{
    protected $signature = 'leave:accrue';

    protected $description = 'Credit automated monthly/half-yearly/annual leave accrual to eligible employees.';

    public function handle(LeaveAccrualService $service): int
    {
        $result = $service->accrueAll();
        $this->info("Leave accrual: {$result['credited']} credited, {$result['skipped']} skipped.");

        return self::SUCCESS;
    }
}
