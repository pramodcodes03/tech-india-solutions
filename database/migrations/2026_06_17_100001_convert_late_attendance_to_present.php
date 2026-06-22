<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // The "Late" attendance status is being removed project-wide: a day is
        // now either a full day (present) or a half-day. Late always counted as
        // a full paid day, so converting late → present is lossless (paid-days
        // totals are unchanged).
        DB::table('attendance')->where('status', 'late')->update(['status' => 'present']);
    }

    public function down(): void
    {
        // One-way data fix — 'late' is no longer a valid status, nothing to revert.
    }
};
