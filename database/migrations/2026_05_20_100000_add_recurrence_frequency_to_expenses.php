<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a frequency dimension to recurring expenses.
 *
 * Previously, type='recurring' implicitly meant "monthly" — the only
 * specifier on the row was `due_day_of_month`. To support weekly /
 * quarterly / half-yearly / yearly recurrences, we now store the
 * cadence explicitly. Existing rows default to 'monthly' so behaviour
 * is preserved for anything created before this migration.
 *
 * For non-monthly frequencies the generator rolls each instance's
 * due_date forward by the appropriate offset (week, 3 months, 6 months,
 * year). `due_day_of_month` continues to be used only for `monthly`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->enum('recurrence_frequency', ['weekly', 'monthly', 'quarterly', 'half_yearly', 'yearly'])
                ->nullable()
                ->after('due_day_of_month');
        });

        // Backfill: every existing recurring row was monthly by design.
        DB::table('expenses')
            ->where('type', 'recurring')
            ->whereNull('recurrence_frequency')
            ->update(['recurrence_frequency' => 'monthly']);
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn('recurrence_frequency');
        });
    }
};
