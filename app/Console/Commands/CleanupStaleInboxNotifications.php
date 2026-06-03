<?php

namespace App\Console\Commands;

use App\Models\AdminNotification;
use App\Models\BankDetailEditRequest;
use App\Models\SalaryStructure;
use Illuminate\Console\Command;

/**
 * Mark unread bell notifications as read when their underlying queue record
 * has already moved on. Backfill for the phantom-notification bug — going
 * forward, the approve/reject handlers clear these inline so this command
 * only needs to be run once after deploy.
 *
 *   php artisan notifications:cleanup-stale          (dry-run)
 *   php artisan notifications:cleanup-stale --apply
 */
class CleanupStaleInboxNotifications extends Command
{
    protected $signature = 'notifications:cleanup-stale {--apply : actually mark rows read; otherwise show counts only}';

    protected $description = 'Clear unread bell notifications whose underlying approval-queue record is no longer pending.';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $jobs = [
            [
                'event' => 'salary_structure.submitted',
                'class' => SalaryStructure::class,
                'pending' => SalaryStructure::STATUS_PENDING,
            ],
            [
                'event' => 'bank_edit.requested',
                'class' => BankDetailEditRequest::class,
                'pending' => BankDetailEditRequest::STATUS_PENDING,
            ],
        ];

        $totalStale = 0;

        foreach ($jobs as $job) {
            $morph = (new $job['class'])->getMorphClass();

            $rows = AdminNotification::query()
                ->whereNull('read_at')
                ->where('event_key', $job['event'])
                ->where('related_type', $morph)
                ->get(['id', 'related_id']);

            if ($rows->isEmpty()) {
                $this->line("  {$job['event']}: 0 unread");
                continue;
            }

            $stillPendingIds = $job['class']::withoutGlobalScopes()
                ->whereIn('id', $rows->pluck('related_id')->unique())
                ->where('status', $job['pending'])
                ->pluck('id')
                ->all();

            $staleIds = $rows
                ->reject(fn ($r) => in_array($r->related_id, $stillPendingIds, true))
                ->pluck('id')
                ->all();

            $totalStale += count($staleIds);

            if (empty($staleIds)) {
                $this->line("  {$job['event']}: 0 stale (all "
                    .count($rows)." unread still point at pending rows)");
                continue;
            }

            $this->line("  {$job['event']}: ".count($staleIds).' stale of '.count($rows).' unread');

            if ($apply) {
                AdminNotification::whereIn('id', $staleIds)->update(['read_at' => now()]);
            }
        }

        if (! $apply) {
            $this->newLine();
            $this->comment("Dry-run. Re-run with --apply to mark {$totalStale} stale row(s) read.");
        } else {
            $this->newLine();
            $this->info("Marked {$totalStale} stale notification(s) as read.");
        }

        return self::SUCCESS;
    }
}
