<?php

namespace App\Console\Commands;

use App\Services\Biometric\BiometricSyncService;
use Illuminate\Console\Command;

/**
 * Pull the latest biometric punches from the shared device API and upsert
 * them as attendance rows. Employees are matched globally by card_no /
 * employee_code — each row lands on whichever business owns the matched
 * employee.
 *
 * Used by:
 *   - Scheduler (every minute, see routes/console.php)
 *   - Manual "Sync Now" button in admin UI
 *
 * Examples:
 *   php artisan attendance:sync-biometric
 *   php artisan attendance:sync-biometric --date=2026-05-15
 */
class SyncBiometricAttendance extends Command
{
    protected $signature = 'attendance:sync-biometric
        {--date= : YYYY-MM-DD to sync (defaults to today)}';

    protected $description = 'Pull today\'s punches from the shared biometric API and upsert as attendance.';

    public function handle(BiometricSyncService $service): int
    {
        $date = $this->option('date') ?: now()->toDateString();

        $result = $service->syncAll($date, 'scheduled');

        $this->line(sprintf(
            'status=%s  punches=%d  matched=%d  upserts=%d  unmatched=%d',
            $result['status'],
            $result['punches_fetched'] ?? 0,
            $result['employees_matched'] ?? 0,
            $result['attendance_upserts'] ?? 0,
            $result['unmatched_cards'] ?? 0,
        ));

        if ($result['status'] === 'failed') {
            $this->error($result['message'] ?? 'unknown error');
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
