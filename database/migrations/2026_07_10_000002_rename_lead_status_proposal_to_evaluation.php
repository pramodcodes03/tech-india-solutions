<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lead workflow update: rename the "proposal" stage to "evaluation" across all
 * businesses. The status is stored as a plain string (no DB enum), so this is a
 * pure data migration — every historical lead and stage-log row is migrated so
 * no data is lost. The new "attempted" stage needs no data change (nothing has
 * ever been stored with that value yet); it is added at the application layer.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('leads')->where('status', 'proposal')->update(['status' => 'evaluation']);

        // Preserve stage-change history consistency (from/to status strings).
        if (Schema::hasTable('lead_stage_logs')) {
            DB::table('lead_stage_logs')->where('from_status', 'proposal')->update(['from_status' => 'evaluation']);
            DB::table('lead_stage_logs')->where('to_status', 'proposal')->update(['to_status' => 'evaluation']);
        }
    }

    public function down(): void
    {
        DB::table('leads')->where('status', 'evaluation')->update(['status' => 'proposal']);

        if (Schema::hasTable('lead_stage_logs')) {
            DB::table('lead_stage_logs')->where('from_status', 'evaluation')->update(['from_status' => 'proposal']);
            DB::table('lead_stage_logs')->where('to_status', 'evaluation')->update(['to_status' => 'proposal']);
        }
    }
};
