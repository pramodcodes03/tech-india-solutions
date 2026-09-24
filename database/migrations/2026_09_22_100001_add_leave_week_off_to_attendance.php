<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Half a day of leave falling on a half week-off.
 *
 *   status += 'leave_week_off'
 *       A day the employee was never expected for: the roster gave them half
 *       of it off and sanctioned leave covers the rest. Until now HR had to
 *       record it as either plain 'on_leave' (which hides the week-off and
 *       spends a whole day of balance) or 'half_day_week_off' (which claims
 *       they worked a half they did not).
 *
 * Mirrors 2026_09_06_100001 and 2026_09_09_100001, which added
 * 'half_day_week_off' and 'half_day_leave' the same way.
 *
 * Note the sibling display status '0.5 Leave / 0.5 Absent' deliberately gets
 * no enum member: it is what a half-day leave with no worked half *derives*
 * to, so storing it would let a row contradict the punches beneath it.
 */
return new class extends Migration
{
    private const ADDED = 'leave_week_off';

    public function up(): void
    {
        // Enum widening has no portable Schema builder equivalent; sqlite has
        // no enum at all and simply stores the string, so it needs nothing.
        if (DB::getDriverName() === 'mysql') {
            $this->setEnum([
                'present', 'absent', 'half_day', 'late', 'on_leave', 'holiday', 'weekend',
                'half_day_week_off', 'half_day_leave', self::ADDED,
            ]);
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // A leave/week-off day is more leave than anything else, so anything
        // left on the new status falls back to plain leave rather than being
        // dropped to a status that would read as a worked half.
        DB::table('attendance')->where('status', self::ADDED)->update(['status' => 'on_leave']);

        $this->setEnum([
            'present', 'absent', 'half_day', 'late', 'on_leave', 'holiday', 'weekend',
            'half_day_week_off', 'half_day_leave',
        ]);
    }

    /** @param  array<int, string>  $values */
    private function setEnum(array $values): void
    {
        $list = implode(',', array_map(fn ($v) => "'{$v}'", $values));

        DB::statement("ALTER TABLE `attendance` MODIFY `status` ENUM({$list}) NOT NULL DEFAULT 'present'");
    }
};
