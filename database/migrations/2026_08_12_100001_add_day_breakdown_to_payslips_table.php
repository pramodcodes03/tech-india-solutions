<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Payslips only stored `working_days`, and payroll was filling it with the
 * CALENDAR day count (31) — so a payslip claimed 31 working days in a month
 * that actually had 27 working days and 4 week-offs, and the week-offs were
 * nowhere to be seen.
 *
 * The attendance summary already computes all of this; these columns simply
 * record it so the payslip can show the real breakdown. Nullable, so payslips
 * generated before this migration keep rendering from `working_days` alone.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->unsignedSmallInteger('calendar_days')->nullable()->after('working_days');
            $table->decimal('week_off_days', 5, 1)->nullable()->after('calendar_days');
            $table->decimal('holiday_days', 5, 1)->nullable()->after('week_off_days');
        });
    }

    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->dropColumn(['calendar_days', 'week_off_days', 'holiday_days']);
        });
    }
};
