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
        // Working days (calendar days since joining) before CL & SL unlock.
        // The EL bucket uses el_working_days_required above. Both are the
        // business-level default; department / employee rows may override.
        'cl_sl_working_days_required' => 90,
        'leave_application_window_hours' => 72,
        'attendance_correction_tat_hours' => 48,
        'ticket_escalation_days' => 3,
        'break_half_day_minutes' => 60,
        // Worked-hours thresholds that decide the daily attendance status:
        //   >= full_day_hours              → Present
        //   >= half_day_hours (and < full) → Half Day
        //   <  half_day_hours              → Absent
        // Set per business, so a 9-hour shift and an 8-hour shift can coexist.
        'full_day_hours' => 9.0,
        'half_day_hours' => 4.5,
        'el_carry_forward_cap' => 30,
        // Leave Balance Gate. When enabled, an employee cannot submit a paid
        // leave request for more days than they actually have left — the system
        // stops it at submission instead of letting HR split it paid/unpaid.
        'leave_balance_gate_enabled' => 1,
        // The exception to that gate: with no paid balance left, may the employee
        // still apply for genuine Leave Without Pay (a medical emergency, say)?
        // On = LWP stays open, only paid types are blocked.
        // Off = the block is absolute; an out-of-balance employee cannot apply
        //       for anything, LWP included.
        'leave_lwp_exception_enabled' => 1,
        'leave_accrual_frequency' => 'monthly', // monthly | half_yearly | annual
        'leave_accrual_day' => 11, // day of the month leave is credited (1-28)
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

    /**
     * Boolean accessor for on/off settings.
     *
     * Values arrive from the settings form as "1"/"0" strings, so a plain cast
     * is not enough — "0" is truthy as a non-empty string in some contexts and
     * an unchecked checkbox posts nothing at all.
     */
    public static function bool(string $key, ?bool $default = null): bool
    {
        return filter_var(
            self::get($key, $default === null ? null : (int) $default),
            FILTER_VALIDATE_BOOL,
        );
    }

    public static function set(string $key, mixed $value, string $group = 'hr'): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value, 'group' => $group]);
        Cache::forget('settings.all');
    }

    /**
     * Per-business variant of get(): read a business-scoped override first,
     * then fall back to the global value, then the coded default. Lets a single
     * setting (e.g. leave_accrual_day) differ per business while the rest of the
     * panel stays global. A null $businessId behaves exactly like get().
     */
    public static function getForBusiness(string $key, ?int $businessId, mixed $default = null): mixed
    {
        if ($businessId !== null) {
            $scoped = Setting::where('key', self::businessKey($key, $businessId))->value('value');
            if ($scoped !== null && $scoped !== '') {
                return $scoped;
            }
        }

        return self::get($key, $default);
    }

    public static function intForBusiness(string $key, ?int $businessId, ?int $default = null): int
    {
        return (int) self::getForBusiness($key, $businessId, $default);
    }

    public static function floatForBusiness(string $key, ?int $businessId, ?float $default = null): float
    {
        return (float) self::getForBusiness($key, $businessId, $default);
    }

    public static function boolForBusiness(string $key, ?int $businessId, ?bool $default = null): bool
    {
        return filter_var(
            self::getForBusiness($key, $businessId, $default === null ? null : (int) $default),
            FILTER_VALIDATE_BOOL,
        );
    }

    /**
     * Per-business variant of set(): stores under a business-namespaced key so
     * each business keeps its own value. A null $businessId writes the global key.
     */
    public static function setForBusiness(string $key, ?int $businessId, mixed $value, string $group = 'hr'): void
    {
        if ($businessId === null) {
            self::set($key, $value, $group);

            return;
        }

        Setting::updateOrCreate(
            ['key' => self::businessKey($key, $businessId)],
            ['value' => $value, 'group' => $group]
        );
        Cache::forget('settings.all');
    }

    private static function businessKey(string $key, int $businessId): string
    {
        return $key.'.b'.$businessId;
    }
}
