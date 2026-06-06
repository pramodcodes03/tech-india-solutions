<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Thin accessor over the global Setting key/value store for HR / payroll /
 * leave thresholds. Every value here is admin-editable from the settings
 * panel — nothing is hard-coded. Callers read a setting with a sensible
 * default so a fresh install behaves reasonably before anything is configured.
 *
 * Settings are global (matching the existing biometric_* convention) rather
 * than per-business; the defaults below are the proposal's "suggested defaults".
 */
class HrSettings
{
    /** Suggested defaults from the quotation's Dynamic Configuration table. */
    public const DEFAULTS = [
        'probation_period_days' => 90,
        'leave_accrual_rate_sl' => 0.5,
        'leave_accrual_rate_cl' => 0.5,
        'leave_accrual_rate_el' => 0.5,
        'el_working_days_required' => 240,
        'leave_application_window_hours' => 72,
        'attendance_correction_tat_hours' => 48,
        'ticket_escalation_days' => 3,
        'break_half_day_minutes' => 60,
        'el_carry_forward_cap' => 30,
        'leave_accrual_frequency' => 'monthly', // monthly | half_yearly | annual
        'leave_cycle' => 'calendar', // calendar (Jan-Dec)
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = Setting::where('key', $key)->value('value');
        if ($value === null || $value === '') {
            return $default ?? self::DEFAULTS[$key] ?? null;
        }

        return $value;
    }

    public static function int(string $key, ?int $default = null): int
    {
        return (int) self::get($key, $default);
    }

    public static function float(string $key, ?float $default = null): float
    {
        return (float) self::get($key, $default);
    }

    public static function set(string $key, mixed $value, string $group = 'hr'): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value, 'group' => $group]);
        Cache::forget('settings.all');
    }
}
