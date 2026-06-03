<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-field locks for attendance punch times.
 *
 * When HR / Business Admin corrects a check_in or check_out from the
 * UI, that specific field gets locked so the daily biometric API sync
 * stops overwriting their manual fix. The other (unlocked) field
 * continues to sync normally — granular control by field, not by row.
 *
 * Locks are per-day: they live on the attendance row, so a new day
 * gets fresh unlocked fields automatically.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $table->boolean('check_in_locked')->default(false)->after('check_in');
            $table->boolean('check_out_locked')->default(false)->after('check_out');
        });
    }

    public function down(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $table->dropColumn(['check_in_locked', 'check_out_locked']);
        });
    }
};
