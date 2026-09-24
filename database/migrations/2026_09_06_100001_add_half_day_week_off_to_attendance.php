<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Half a day worked, half a day week-off.
 *
 * Two additions:
 *
 *   status += 'half_day_week_off'
 *       A day that is 0.5 present + 0.5 week-off. Distinct from 'half_day'
 *       (where the other half is unpaid absence) and from 'weekend' (where no
 *       work happened at all), because it pays differently and has to be
 *       distinguishable on the calendar and in the register.
 *
 *   half_day_portion
 *       WHICH half was worked — 'first_half' means the employee worked the
 *       morning and the afternoon is the week-off. Without it a half-day row
 *       cannot say whether the duty time shown belongs at the start or the end
 *       of the day, which is exactly what the calendar has to display.
 *
 * The column is deliberately usable by plain 'half_day' rows too, so
 * "Half Day / Leave" can also say which half was worked.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $table->enum('half_day_portion', ['first_half', 'second_half'])
                ->nullable()
                ->after('status');
        });

        // Enum widening has no portable Schema builder equivalent; sqlite has
        // no enum at all and simply stores the string, so it needs nothing.
        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                'ALTER TABLE `attendance` MODIFY `status` ENUM('
                ."'present','absent','half_day','late','on_leave','holiday','weekend','half_day_week_off'"
                .") NOT NULL DEFAULT 'present'"
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // Anything on the new status becomes a plain half-day rather than
            // being lost outright.
            DB::table('attendance')->where('status', 'half_day_week_off')->update(['status' => 'half_day']);

            DB::statement(
                'ALTER TABLE `attendance` MODIFY `status` ENUM('
                ."'present','absent','half_day','late','on_leave','holiday','weekend'"
                .") NOT NULL DEFAULT 'present'"
            );
        }

        Schema::table('attendance', function (Blueprint $table) {
            $table->dropColumn('half_day_portion');
        });
    }
};
