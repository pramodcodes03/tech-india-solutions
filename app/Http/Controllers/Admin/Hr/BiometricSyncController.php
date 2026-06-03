<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Services\Biometric\BiometricSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Manual "Sync Now" trigger. Pulls today's punches from the shared biometric
 * API right now; mirrors what the scheduler does every minute.
 */
class BiometricSyncController extends Controller
{
    public function __construct(protected BiometricSyncService $service) {}

    public function sync(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('attendance.import'), 403);

        if (! $this->service->isEnabled() || ! $this->service->getUrl()) {
            return back()->with('error', 'Biometric sync is not configured. Set the API URL and enable it in Settings → Biometric.');
        }

        $validated = $request->validate([
            'date' => ['nullable', 'date', 'before_or_equal:today'],
        ], [
            'date.before_or_equal' => 'Cannot sync a future date — the device has no data for it yet.',
        ]);

        $date = $validated['date'] ?? now()->toDateString();

        $result = $this->service->syncAll($date, 'manual:'.Auth::guard('admin')->id());

        if ($result['status'] === 'failed') {
            return back()->with('error', 'Biometric sync failed: '.($result['message'] ?? 'unknown error'));
        }

        $msg = sprintf(
            'Biometric sync complete for %s — fetched %d punches, matched %d employees, %d attendance rows written, %d unmatched cards.',
            $date,
            $result['punches_fetched'],
            $result['employees_matched'],
            $result['attendance_upserts'],
            $result['unmatched_cards'],
        );

        return back()->with('success', $msg);
    }
}
