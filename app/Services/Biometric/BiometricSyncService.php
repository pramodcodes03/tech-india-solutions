<?php

namespace App\Services\Biometric;

use App\Models\Employee;
use App\Models\Setting;
use App\Services\AttendanceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Orchestrates a single biometric sync run for one date.
 *
 * The vendor exposes one device API (shared across all tenants). Employee
 * codes / card numbers are globally unique, so a punch is routed to whichever
 * business the matched employee lives in — automatically.
 *
 * Pipeline:
 *   1. Read the global biometric URL + enabled flag from settings.
 *   2. Fetch raw punches via BiometricApiClient.
 *   3. Aggregate per card_number:  min(punch) → check_in, max(punch) → check_out.
 *   4. Resolve each card_number to an employees row (card_no first, then employee_code).
 *   5. Upsert attendance via AttendanceService → inherits the comp-off guard.
 *   6. Write a biometric_sync_logs row with counts and any error.
 */
class BiometricSyncService
{
    public function __construct(
        protected BiometricApiClient $client,
        protected AttendanceService $attendance,
    ) {}

    /**
     * Run a sync for one date. Returns aggregate counts.
     *
     * @return array{
     *     status: 'success'|'failed'|'skipped',
     *     message?: string,
     *     punches_fetched: int,
     *     employees_matched: int,
     *     attendance_upserts: int,
     *     unmatched_cards: int,
     *     unmatched_card_list: array<int,string>,
     * }
     */
    public function syncAll(string $date, string $triggeredBy = 'scheduled'): array
    {
        $url = $this->getUrl();
        if (! $this->isEnabled() || ! $url) {
            return [
                'status'              => 'skipped',
                'message'             => 'Biometric sync is disabled or API URL is empty (Settings → Biometric).',
                'punches_fetched'     => 0,
                'employees_matched'   => 0,
                'attendance_upserts'  => 0,
                'unmatched_cards'     => 0,
                'unmatched_card_list' => [],
            ];
        }

        $logId = DB::table('biometric_sync_logs')->insertGetId([
            'business_id'  => null,
            'sync_date'    => $date,
            'started_at'   => now(),
            'status'       => 'running',
            'triggered_by' => $triggeredBy,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        try {
            $punches = $this->client->fetchPunches($url, $date);
        } catch (Throwable $e) {
            $this->finishLog($logId, [
                'status'          => 'failed',
                'finished_at'     => now(),
                'error_message'   => substr($e->getMessage(), 0, 1000),
                'updated_at'      => now(),
            ]);

            Log::error("[Biometric] Sync failed for {$date}: {$e->getMessage()}");

            return [
                'status'              => 'failed',
                'message'             => $e->getMessage(),
                'punches_fetched'     => 0,
                'employees_matched'   => 0,
                'attendance_upserts'  => 0,
                'unmatched_cards'     => 0,
                'unmatched_card_list' => [],
            ];
        }

        $byCard = $punches->groupBy('card_number')->map(function ($group) {
            $sorted = $group->sortBy(fn ($p) => $p['punch_at']->getTimestamp());

            return [
                'first' => $sorted->first()['punch_at'],
                'last'  => $sorted->last()['punch_at'],
                'count' => $sorted->count(),
            ];
        });

        $matched = 0;
        $upserts = 0;
        $unmatchedCards = [];

        foreach ($byCard as $card => $info) {
            $employee = $this->resolveEmployee((string) $card);
            if (! $employee) {
                $unmatchedCards[] = (string) $card;
                continue;
            }

            $matched++;

            $checkIn  = $info['first']->format('H:i:s');
            $checkOut = $info['count'] > 1 ? $info['last']->format('H:i:s') : null;

            $result = $this->attendance->upsert([
                'employee_id' => $employee->id,
                'business_id' => $employee->business_id,
                'date'        => $date,
                'check_in'    => $checkIn,
                'check_out'   => $checkOut,
                'card_no'     => $card,
                'source'      => 'biometric_api',
            ]);

            if ($result !== null) {
                $upserts++;
            }
        }

        $unmatchedCount = count($unmatchedCards);

        $this->finishLog($logId, [
            'status'              => 'success',
            'finished_at'         => now(),
            'punches_fetched'     => $punches->count(),
            'employees_matched'   => $matched,
            'attendance_upserts'  => $upserts,
            'unmatched_cards'     => $unmatchedCount,
            'unmatched_card_list' => json_encode(array_slice($unmatchedCards, 0, 100)),
            'updated_at'          => now(),
        ]);

        Setting::updateOrCreate(
            ['key' => 'biometric_last_synced_at'],
            ['value' => now()->toDateTimeString(), 'group' => 'biometric'],
        );

        return [
            'status'              => 'success',
            'punches_fetched'     => $punches->count(),
            'employees_matched'   => $matched,
            'attendance_upserts'  => $upserts,
            'unmatched_cards'     => $unmatchedCount,
            'unmatched_card_list' => $unmatchedCards,
        ];
    }

    public function isEnabled(): bool
    {
        $val = Setting::where('key', 'biometric_enabled')->value('value');
        return in_array((string) $val, ['1', 'true', 'on', 'yes'], true);
    }

    public function getUrl(): ?string
    {
        $url = Setting::where('key', 'biometric_api_url')->value('value');
        return is_string($url) && $url !== '' ? $url : null;
    }

    public function getLastSyncedAt(): ?string
    {
        return Setting::where('key', 'biometric_last_synced_at')->value('value');
    }

    /**
     * Match a card_number to an Employee, globally. Tries `card_no` first, then
     * `employee_code`. Skips inactive / terminated / absconded employees.
     */
    protected function resolveEmployee(string $card): ?Employee
    {
        $card = trim($card);
        if ($card === '') {
            return null;
        }

        $excluded = ['inactive', 'terminated', 'absconded'];

        $hit = Employee::withoutGlobalScopes()
            ->where('card_no', $card)
            ->whereNotIn('status', $excluded)
            ->first(['id', 'business_id']);

        if ($hit) {
            return $hit;
        }

        return Employee::withoutGlobalScopes()
            ->where('employee_code', $card)
            ->whereNotIn('status', $excluded)
            ->first(['id', 'business_id']);
    }

    protected function finishLog(int $logId, array $patch): void
    {
        DB::table('biometric_sync_logs')->where('id', $logId)->update($patch);
    }
}
