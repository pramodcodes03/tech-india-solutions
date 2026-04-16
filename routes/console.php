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
