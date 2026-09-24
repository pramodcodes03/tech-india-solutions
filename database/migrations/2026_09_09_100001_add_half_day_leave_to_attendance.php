<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Half a day worked, half a day sanctioned leave.
 *
 *   status += 'half_day_leave'
 *       A day that is 0.5 present + 0.5 approved leave. Until now approving a
 *       leave stamped the whole day 'on_leave' regardless of the portion, so a
 *       manager who worked the morning and took the afternoon off read as a
 *       full day's leave on the attendance list, the calendar and the register.
 *
 * The calendar has always been able to *derive* this status (a 'half_day' row
 * plus an approved leave on the same date), and that derivation stays as a
 * backstop for legacy rows. Storing it makes the row itself say what the day
 * was, so every screen reading the column directly agrees with the calendar.
 *
 * Mirrors 2026_09_06_100001, which added 'half_day_week_off' the same way.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Enum widening has no portable Schema builder equivalent; sqlite has
        // no enum at all and simply stores the string, so it needs nothing.
        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                'ALTER TABLE `attendance` MODIFY `status` ENUM('
                ."'present','absent','half_day','late','on_leave','holiday','weekend',"
                ."'half_day_week_off','half_day_leave'"
                .") NOT NULL DEFAULT 'present'"
            );
        }

        // Repair the rows the old approval path already mis-stamped: marked
        // 'on_leave' by a leave approval, but carrying punch times that prove
        // the employee worked part of that day against a half-day leave.
        // The worked half is the one the leave did NOT cover.
        $mistamped = DB::table('attendance as a')
            ->join('leave_requests as l', function ($join) {
                $join->on('l.employee_id', '=', 'a.employee_id')
                    ->whereColumn('l.from_date', '<=', 'a.date')
                    ->whereColumn('l.to_date', '>=', 'a.date');
            })
            ->where('a.status', 'on_leave')
            ->whereNotNull('a.check_in')
            ->where('a.hours_worked', '>', 0)
            ->where('l.status', 'approved')
            ->whereIn('l.day_portion', ['first_half', 'second_half'])
            ->select('a.id', 'l.day_portion')
            ->get();

        foreach ($mistamped as $row) {
            DB::table('attendance')->where('id', $row->id)->update([
                'status' => 'half_day_leave',
                'half_day_portion' => $row->day_portion === 'first_half' ? 'second_half' : 'first_half',
            ]);
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // Back to how these days were recorded before: a full day of
            // leave. The punch times stay on the row either way.
            DB::table('attendance')->where('status', 'half_day_leave')->update(['status' => 'on_leave']);

            DB::statement(
                'ALTER TABLE `attendance` MODIFY `status` ENUM('
                ."'present','absent','half_day','late','on_leave','holiday','weekend','half_day_week_off'"
                .") NOT NULL DEFAULT 'present'"
            );
        }
    }
};
