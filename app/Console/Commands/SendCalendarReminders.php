<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\Holiday;
use App\Notifications\NotificationDispatcher;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Daily calendar reminders:
 *
 *  - holiday.upcoming   for any holiday exactly 2 days from today
 *  - employee.birthday  for any employee whose birthday is today
 *
 * Walks every business at once (uses withoutGlobalScopes); each entity
 * carries its own business_id which the dispatcher uses to route events.
 */
class SendCalendarReminders extends Command
{
    protected $signature = 'calendar:send-reminders {--dry-run}';

    protected $description = 'Fire holiday.upcoming (2 days out) + employee.birthday (today) events';

    public function handle(): int
    {
        $today = Carbon::today();
        $dryRun = $this->option('dry-run');

        // Holidays exactly 2 days from now.
        $upcomingHolidays = Holiday::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->whereDate('date', $today->copy()->addDays(2))
            ->get();

        $this->info("Found {$upcomingHolidays->count()} holiday(s) in 2 days.");

        foreach ($upcomingHolidays as $holiday) {
            $this->line("  → holiday.upcoming for {$holiday->name} ({$holiday->date->toDateString()})");
            if (! $dryRun) {
                NotificationDispatcher::fire('holiday.upcoming', $holiday, [
                    'day_name' => $holiday->date->format('l'),
                ]);
            }
        }

        // Employees with birthday matching today (any year).
        $birthdayEmployees = Employee::withoutGlobalScopes()
            ->whereNotNull('date_of_birth')
            ->whereRaw('MONTH(date_of_birth) = ? AND DAY(date_of_birth) = ?', [
                $today->month, $today->day,
            ])
            ->whereIn('status', ['active', 'probation'])
            ->whereNull('deleted_at')
            ->get();

        $this->info("Found {$birthdayEmployees->count()} birthday employee(s) today.");

        foreach ($birthdayEmployees as $employee) {
            $this->line("  → employee.birthday for {$employee->employee_code} ({$employee->first_name})");
            if (! $dryRun) {
                NotificationDispatcher::fire('employee.birthday', $employee);
            }
        }

        return self::SUCCESS;
    }
}
