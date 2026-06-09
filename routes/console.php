<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Commands
|--------------------------------------------------------------------------
*/

Schedule::command('invoices:mark-overdue')->dailyAt('00:05');
Schedule::command('stock:low-stock-alert')->dailyAt('08:00');
Schedule::command('docs:generate-routes')->daily();
Schedule::command('backup:run --only-db')->dailyAt('02:00');

// Expense management: spawn next month's recurring rows daily, then send
// any due/overdue reminder emails. Order matters — generate first, remind
// second, so a freshly-generated row can also pick up its T-3 reminder.
Schedule::command('expenses:generate-recurring')->dailyAt('00:30');
Schedule::command('expenses:send-reminders')->dailyAt('09:00');

// Invoice reminders: T-3 and daily overdue.
Schedule::command('invoices:send-reminders')->dailyAt('09:30');

// Calendar: holiday-upcoming (T-2) + employee birthdays (today).
Schedule::command('calendar:send-reminders')->dailyAt('08:00');

// Biometric attendance: pull today's punches every minute from each enabled
// business's external device API. ->withoutOverlapping() guards against a slow
// API run colliding with the next tick. ->runInBackground() so the scheduler
// doesn't wait on the HTTP round-trip.
Schedule::command('attendance:sync-biometric')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->runInBackground();

// Attendance regularization: flag requests past their TAT window every hour.
Schedule::command('attendance:escalate-regularizations')->hourly();

// Internal helpdesk: escalate overdue tickets per the escalation matrix.
Schedule::command('tickets:escalate-internal')->hourly();

// Leave: daily accrual credit + year-end lapse / carry-forward on Dec 31.
Schedule::command('leave:accrue')->dailyAt('01:00');
Schedule::command('leave:year-end')->yearlyOn(12, 31, '23:30');
