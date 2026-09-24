<?php

namespace Tests\Unit;

use App\Services\Attendance\AttendanceStatusCalculator as Calc;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The attendance rules, pinned. Pure PHPUnit — the calculator touches no
 * database, so these run in milliseconds.
 */
class AttendanceStatusCalculatorTest extends TestCase
{
    // ── Branch 1 — no shift assigned (dynamic hours) ────────────────────────

    #[Test]
    #[DataProvider('dynamicHoursCases')]
    public function dynamic_hours_branch(float $worked, string $expected): void
    {
        $this->assertSame($expected, Calc::fromDynamicHours($worked, 9.0, 4.5));
    }

    public static function dynamicHoursCases(): array
    {
        return [
            'full day exactly' => [9.0, Calc::PRESENT],
            'overtime' => [10.5, Calc::PRESENT],
            'one minute short' => [8.98, Calc::HALF_DAY],
            'half day exactly' => [4.5, Calc::HALF_DAY],
            'just under half' => [4.49, Calc::ABSENT],
            'nothing' => [0.0, Calc::ABSENT],
        ];
    }

    // ── Branch 2 — shift assigned (strict shift window) ─────────────────────

    /** Shift 09:00–18:00 → window 540 min, half-window 270 min. */
    #[Test]
    #[DataProvider('dayShiftCases')]
    public function day_shift_window(string $in, string $out, string $expected, string $why): void
    {
        $this->assertSame(
            $expected,
            Calc::fromShiftWindow('09:00:00', '18:00:00', $in, $out),
            $why
        );
    }

    public static function dayShiftCases(): array
    {
        return [
            'covers the whole shift' => ['09:00', '18:00', Calc::PRESENT,  'exact shift'],
            'in early, out late' => ['08:00', '19:00', Calc::PRESENT,  'window fully covered'],
            'early in, early out' => ['08:30', '17:30', Calc::HALF_DAY, 'overlap 8h30m, last 30m missed'],
            'late in, late out' => ['10:00', '19:00', Calc::HALF_DAY, 'overlap 8h, first hour missed'],
            'exactly half the window' => ['13:30', '18:00', Calc::HALF_DAY, 'overlap 4h30m = half'],
            'one minute under half' => ['13:31', '18:00', Calc::ABSENT,   'overlap 4h29m'],
            'afternoon only' => ['14:00', '18:00', Calc::ABSENT,   'overlap 4h'],
            'entirely before the shift' => ['05:00', '08:00', Calc::ABSENT,   'no overlap at all'],
            'entirely after the shift' => ['19:00', '22:00', Calc::ABSENT,   'no overlap at all'],
            'same-minute double punch' => ['11:44', '11:44', Calc::ABSENT,   'not a 24-hour day'],
        ];
    }

    #[Test]
    public function grace_covers_a_slightly_late_arrival(): void
    {
        // 12 minutes late with a 15-minute grace still counts as covering the
        // start of the window.
        $this->assertSame(
            Calc::PRESENT,
            Calc::fromShiftWindow('09:00:00', '18:00:00', '09:12', '18:00', 15)
        );

        // Beyond grace the missed minutes count against the overlap.
        $this->assertSame(
            Calc::HALF_DAY,
            Calc::fromShiftWindow('09:00:00', '18:00:00', '09:20', '18:00', 15)
        );

        // Grace never extends the end of the shift.
        $this->assertSame(
            Calc::HALF_DAY,
            Calc::fromShiftWindow('09:00:00', '18:00:00', '09:00', '17:45', 15)
        );
    }

    /** Night shift 18:30–06:30 → window 720 min, half-window 360 min. */
    #[Test]
    #[DataProvider('nightShiftCases')]
    public function night_shift_window(string $in, string $out, string $expected, string $why): void
    {
        $this->assertSame(
            $expected,
            Calc::fromShiftWindow('18:30:00', '06:30:00', $in, $out),
            $why
        );
    }

    public static function nightShiftCases(): array
    {
        return [
            'full night' => ['18:30', '06:30', Calc::PRESENT,  'crosses midnight, fully covered'],
            'in early, out late' => ['18:00', '07:00', Calc::PRESENT,  'window fully covered'],
            'late in' => ['19:30', '06:30', Calc::HALF_DAY, 'overlap 11h, first hour missed'],
            'left at midnight' => ['18:30', '00:30', Calc::HALF_DAY, 'overlap 6h = exactly half'],
            'left just before half' => ['18:30', '00:29', Calc::ABSENT,   'overlap 5h59m'],
            'day-time punches' => ['09:00', '17:00', Calc::ABSENT,   'no overlap with the night window'],
        ];
    }

    // ── Edge cases / bad data ───────────────────────────────────────────────

    #[Test]
    public function unparseable_or_impossible_input_never_credits_a_day(): void
    {
        $this->assertSame(Calc::ABSENT, Calc::fromShiftWindow('09:00', '18:00', 'not-a-time', '18:00'));
        $this->assertSame(Calc::ABSENT, Calc::fromShiftWindow('09:00', '18:00', '25:00', '18:00'));
        // A reversed pair (PM typed as AM in a correction) would wrap to ~20h —
        // treated as bad data rather than a very long day.
        $this->assertSame(Calc::ABSENT, Calc::fromShiftWindow('09:00', '18:00', '08:39', '05:35'));
        // Misconfigured shift (zero-length window).
        $this->assertSame(Calc::ABSENT, Calc::fromShiftWindow('09:00', '09:00', '09:00', '18:00'));
    }

    #[Test]
    public function overlap_minutes_are_exposed_for_reporting(): void
    {
        $this->assertSame(540, Calc::overlapMinutes('09:00', '18:00', '09:00', '18:00'));
        $this->assertSame(480, Calc::overlapMinutes('09:00', '18:00', '10:00', '19:00'));
        $this->assertSame(0, Calc::overlapMinutes('09:00', '18:00', '19:00', '22:00'));
        $this->assertNull(Calc::overlapMinutes('09:00', '18:00', 'x', '18:00'));
    }

    #[Test]
    public function window_length_handles_night_shifts_and_rejects_nonsense(): void
    {
        $this->assertSame(540, Calc::windowMinutes('09:00', '18:00'));
        $this->assertSame(720, Calc::windowMinutes('18:30', '06:30'));
        $this->assertNull(Calc::windowMinutes('08:00', '05:00'));   // 21h — bad data
        $this->assertNull(Calc::windowMinutes('09:00', '09:00'));   // zero length
    }
}
