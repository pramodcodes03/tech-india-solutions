<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optional per-department override of the "EL Working-days Required" threshold.
 * Applies to every employee in the department that has no per-employee value of
 * its own. NULL = inherit (leave type's min_working_days → global setting).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->unsignedInteger('el_working_days_required')->nullable()->after('head_id');
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn('el_working_days_required');
        });
    }
};
