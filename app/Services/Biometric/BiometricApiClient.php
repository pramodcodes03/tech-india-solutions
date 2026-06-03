<?php

namespace App\Services\Biometric;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * HTTP wrapper for the third-party biometric attendance API.
 *
 * The vendor returns JSON shaped like:
 *   { "STATUS": "true", "Data": [ {card_number, NAME, Punch_Date, ...}, ... ], "Error": "" }
 *
 * This client normalises the response into a Collection of arrays:
 *   [
 *     ['card_number' => '1300', 'name' => 'Gurbir Singh', 'punch_at' => Carbon, ...],
 *     ...
 *   ]
 *
 * It does NOT touch the database. The sync service is responsible for matching
 * card numbers to employees and creating attendance rows.
 */
class BiometricApiClient
{
    /** Request timeout in seconds. */
    protected int $timeoutSeconds = 15;

    /** Retry on transient network errors. */
    protected int $maxRetries = 2;

    /** Delay between retries in ms. */
    protected int $retryDelayMs = 500;

    /**
     * Fetch all punches for the given date from the given base URL.
     *
     * @param  string  $baseUrl  e.g. "http://112.196.54.213/Service/Attendance/EmployeeAttendance_DateWise"
     * @param  string  $date     Y-m-d string
     * @return Collection<int, array{card_number: string, name: string, punch_at: \Carbon\Carbon, is_finger: bool, is_rf: bool}>
     *
     * @throws RuntimeException on network/parse failure
     */
    public function fetchPunches(string $baseUrl, string $date): Collection
    {
        $url = $this->buildUrl($baseUrl, $date);

        try {
            $response = Http::timeout($this->timeoutSeconds)
                ->retry($this->maxRetries, $this->retryDelayMs)
                ->acceptJson()
                ->get($url);
        } catch (\Throwable $e) {
            throw new RuntimeException("Biometric API request failed: {$e->getMessage()}", 0, $e);
        }

        if (! $response->successful()) {
            throw new RuntimeException("Biometric API HTTP {$response->status()} from {$url}");
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException('Biometric API returned non-JSON body.');
        }

        // Vendor returns string "true"/"false" rather than booleans.
        $status = strtolower((string) ($payload['STATUS'] ?? 'false'));
        if ($status !== 'true') {
            $err = trim((string) ($payload['Error'] ?? ''));
            throw new RuntimeException('Biometric API status=false'.($err !== '' ? ": {$err}" : ''));
        }

        $rows = $payload['Data'] ?? [];
        if (! is_array($rows)) {
            return collect();
        }

        return collect($rows)
            ->map(function ($r) {
                try {
                    $punch = \Carbon\Carbon::parse($r['Punch_Date'] ?? null);
                } catch (\Throwable) {
                    return null;
                }

                $card = trim((string) ($r['card_number'] ?? ''));
                if ($card === '') {
                    return null;
                }

                return [
                    'card_number' => $card,
                    'name'        => trim((string) ($r['NAME'] ?? '')),
                    'punch_at'    => $punch,
                    'is_finger'   => strcasecmp(trim((string) ($r['Is_Fingure_Based'] ?? '')), 'yes') === 0,
                    'is_rf'       => strcasecmp(trim((string) ($r['Is_RF_Based'] ?? '')), 'yes') === 0,
                ];
            })
            ->filter()
            ->values();
    }

    protected function buildUrl(string $baseUrl, string $date): string
    {
        $base = rtrim($baseUrl, '?&');

        return $base.(str_contains($base, '?') ? '&' : '?').'Date_Time='.urlencode($date);
    }
}
