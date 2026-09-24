<?php

namespace App\Services\Attendance;

/**
 * Decides the daily attendance status: Present / Half-Day / Absent.
 *
 * Pure calculation — no database, no models, no framework state. Everything it
 * needs is passed in, which keeps it unit-testable and makes the rules easy to
 * audit when HR asks "why was this day a half day?".
 *
 * There are two completely separate branches, chosen by whether the employee
 * has a shift assigned:
 *
 *  ── Branch 1 — NO shift (dynamic hours) ───────────────────────────────────
 *  Judged purely on how long the person worked, against the per-business
 *  thresholds configured in Leave Settings (defaults: 9h full, 4.5h half).
 *
 *      worked >= full  → Present
 *      worked >= half  → Half-Day
 *      otherwise       → Absent
 *
 *  ── Branch 2 — shift assigned (strict shift window) ───────────────────────
 *  Only the time that OVERLAPS the shift window counts. Time worked outside
 *  the window (came early and left early, came late and left late) is ignored.
 *
 *      overlap covers the whole window → Present
 *      overlap >= half the window      → Half-Day
 *      otherwise                       → Absent
 *
 *  Example — shift 09:00–18:00 (9h, half = 4h30m):
 *      09:00–18:00 → overlap 9h00m → Present
 *      08:30–17:30 → overlap 8h30m → Half-Day  (left 30m of the shift unworked)
 *      10:00–19:00 → overlap 8h00m → Half-Day  (missed the first hour)
 *      14:00–18:00 → overlap 4h00m → Absent    (under the 4h30m half-window)
 *      19:00–22:00 → overlap 0h00m → Absent    (entirely outside the shift)
 *
 * Night shifts (e.g. 18:30–06:30) are handled without special-casing: every
 * time is projected onto a single timeline measured in minutes from the shift's
 * own start, so "crossing midnight" simply becomes a larger number.
 */
final class AttendanceStatusCalculator
{
    public const PRESENT = 'present';

    public const HALF_DAY = 'half_day';

    public const ABSENT = 'absent';

    /** Minutes in a day — used to roll times across midnight. */
    private const DAY = 1440;

    /**
     * A punch pair longer than this is treated as bad data rather than a real
     * (very long) day — e.g. a correction saved with a PM time entered as AM.
     */
    private const MAX_SANE_WORK_MINUTES = 16 * 60;

    /**
     * Branch 1 — no shift assigned.
     *
     * @param  float  $workedHours  Total time between punch-in and punch-out.
     * @param  float  $fullDayHours  Leave Settings: hours for a full day.
     * @param  float  $halfDayHours  Leave Settings: hours for a half day.
     */
    public static function fromDynamicHours(float $workedHours, float $fullDayHours, float $halfDayHours): string
    {
        if ($workedHours >= $fullDayHours) {
            return self::PRESENT;
        }

        if ($workedHours >= $halfDayHours) {
            return self::HALF_DAY;
        }

        return self::ABSENT;
    }

    /**
     * Branch 2 — shift assigned. Judged on the overlap with the shift window.
     *
     * @param  string  $shiftStart  "H:i" or "H:i:s"
     * @param  string  $shiftEnd  "H:i" or "H:i:s" (may be before start = night shift)
     * @param  string  $checkIn  "H:i" or "H:i:s"
     * @param  string  $checkOut  "H:i" or "H:i:s"
     * @param  int  $graceMinutes  Late-arrival tolerance: arriving up to this many
     *                             minutes after the shift start still counts as
     *                             having covered the start of the window.
     */
    public static function fromShiftWindow(
        string $shiftStart,
        string $shiftEnd,
        string $checkIn,
        string $checkOut,
        int $graceMinutes = 0,
    ): string {
        $window = self::windowMinutes($shiftStart, $shiftEnd);

        // Unusable shift configuration — the caller should have fallen back to
        // Branch 1, but never guess a status from nonsense.
        if ($window === null) {
            return self::ABSENT;
        }

        $overlap = self::overlapMinutes($shiftStart, $shiftEnd, $checkIn, $checkOut, $graceMinutes);

        if ($overlap === null) {
            return self::ABSENT;
        }

        // Covered the whole shift (grace already folded into the overlap).
        if ($overlap >= $window) {
            return self::PRESENT;
        }

        // Half the shift's own duration — a 9h shift needs 4h30m, a 12h night
        // shift needs 6h, a 6h evening shift needs 3h.
        if ($overlap >= (int) ceil($window / 2)) {
            return self::HALF_DAY;
        }

        return self::ABSENT;
    }

    /**
     * Minutes of the punch pair that fall inside the shift window.
     *
     * Returns null when the times can't be parsed or the punch pair is
     * implausibly long (bad data). Exposed publicly so reports and tests can
     * show the "valid hours" behind a status.
     */
    public static function overlapMinutes(
        string $shiftStart,
        string $shiftEnd,
        string $checkIn,
        string $checkOut,
        int $graceMinutes = 0,
    ): ?int {
        $window = self::windowMinutes($shiftStart, $shiftEnd);
        $start = self::toMinutes($shiftStart);
        $in = self::toMinutes($checkIn);
        $out = self::toMinutes($checkOut);

        if ($window === null || $start === null || $in === null || $out === null) {
            return null;
        }

        // Everything below is measured in minutes from the shift's start:
        //   0 .............................. $window
        //   ^ shift starts                   ^ shift ends
        $shiftOpens = 0;
        $shiftCloses = $window;

        // Where the employee arrived, relative to the shift start. Wrapped into
        // ±12h so an early arrival reads as a small negative number rather than
        // "23 hours late", and a night-shift punch after midnight reads as a
        // small positive one.
        $arrived = self::wrapHalfDay($in - $start);

        // How long they actually stayed. A non-positive difference means the
        // punch-out is on the next calendar day (night shift).
        $worked = $out - $in;
        if ($worked < 0) {
            $worked += self::DAY;
        }

        // in == out is a double punch on the same minute, not a 24-hour day.
        if ($worked <= 0 || $worked > self::MAX_SANE_WORK_MINUTES) {
            return 0;
        }

        $left = $arrived + $worked;

        // Grace: arriving a few minutes late still counts as being there when
        // the shift opened. It never extends the end of the window.
        if ($graceMinutes > 0 && $arrived > 0 && $arrived <= $graceMinutes) {
            $arrived = 0;
        }

        // Classic interval intersection, clamped so it can never go negative.
        $overlapStart = max($arrived, $shiftOpens);
        $overlapEnd = min($left, $shiftCloses);

        return max(0, $overlapEnd - $overlapStart);
    }

    /**
     * Length of the shift window in minutes, rolling past midnight for night
     * shifts. Returns null for a zero-length or implausibly long window, which
     * always means the shift record is misconfigured.
     */
    public static function windowMinutes(string $shiftStart, string $shiftEnd): ?int
    {
        $start = self::toMinutes($shiftStart);
        $end = self::toMinutes($shiftEnd);

        if ($start === null || $end === null) {
            return null;
        }

        $length = $end - $start;
        if ($length <= 0) {
            $length += self::DAY;      // night shift, e.g. 18:30 → 06:30
        }

        return ($length > 0 && $length <= self::MAX_SANE_WORK_MINUTES) ? $length : null;
    }

    /** "H:i" / "H:i:s" → minutes since midnight, or null when unparseable. */
    private static function toMinutes(?string $time): ?int
    {
        if (! $time || ! preg_match('/^(\d{1,2}):(\d{2})/', trim($time), $m)) {
            return null;
        }

        $hours = (int) $m[1];
        $minutes = (int) $m[2];

        if ($hours > 23 || $minutes > 59) {
            return null;
        }

        return $hours * 60 + $minutes;
    }

    /** Fold a minute offset into (−12h, +12h] so "early" stays negative. */
    private static function wrapHalfDay(int $minutes): int
    {
        if ($minutes > self::DAY / 2) {
            return $minutes - self::DAY;
        }

        if ($minutes <= -self::DAY / 2) {
            return $minutes + self::DAY;
        }

        return $minutes;
    }
}
