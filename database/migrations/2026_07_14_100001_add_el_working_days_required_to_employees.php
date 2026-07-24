<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optional per-employee override of the "EL Working-days Required" threshold.
 * When set, this employee's Earned-Leave accrual gate uses this value instead
 * of the department override, the leave type's min_working_days, or the global
 * el_working_days_required setting. NULL = inherit (department → type → global).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedInteger('el_working_days_required')->nullable()->after('probation_end_date');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('el_working_days_required');
        });
    }
};
