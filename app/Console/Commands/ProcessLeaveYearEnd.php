<?php

namespace App\Console\Commands;

use App\Services\LeaveAccrualService;
use Illuminate\Console\Command;

/**
 * Year-end leave processing — lapses unused non-carry-forward balances,
 * carries forward eligible earned leave up to the per-type cap, and seeds
 * next year's balances. Scheduled for Dec 31; can also be run manually with
 * --year=YYYY to (re)process a specific year.
 */
class ProcessLeaveYearEnd extends Command
{
    protected $signature = 'leave:year-end {--year= : the calendar year to close (defaults to current)}';

    protected $description = 'Lapse / carry-forward leave balances at the end of the calendar year.';

    public function handle(LeaveAccrualService $service): int
    {
        $year = $this->option('year') ? (int) $this->option('year') : null;
        $result = $service->processYearEnd($year);
        $this->info("Year-end: {$result['lapsed']} balances lapsed, {$result['carried']} carried forward.");

        return self::SUCCESS;
    }
}
