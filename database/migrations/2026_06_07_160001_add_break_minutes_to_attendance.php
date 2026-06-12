<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Break duration per attendance day. When it exceeds the configurable
 * break-policy threshold, the day is auto-marked half-day, which flows into
 * payroll as a 0.5-day loss of pay via the existing paid-days proration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $table->unsignedInteger('break_minutes')->nullable()->after('over_time');
        });
    }

    public function down(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $table->dropColumn('break_minutes');
        });
    }
};
