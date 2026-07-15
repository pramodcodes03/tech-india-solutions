<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two-bucket leave working-days gate, overridable at employee / department /
 * business level (most specific wins).
 *
 * The existing `el_working_days_required` column (on both tables) already holds
 * the EL-bucket override, so we only add the CL & SL bucket here. NULL on either
 * column = "inherit from the next level down" (employee → department → global).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedInteger('cl_sl_working_days')->nullable()->after('el_working_days_required');
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->unsignedInteger('cl_sl_working_days')->nullable()->after('el_working_days_required');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('cl_sl_working_days');
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn('cl_sl_working_days');
        });
    }
};
