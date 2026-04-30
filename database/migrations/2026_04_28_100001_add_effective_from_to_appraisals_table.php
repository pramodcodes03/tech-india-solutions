<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appraisals', function (Blueprint $table) {
            if (! Schema::hasColumn('appraisals', 'effective_from')) {
                $table->date('effective_from')->nullable()->after('new_ctc_annual');
                $table->index('effective_from');
            }
        });

        // Extend the status enum to include 'acknowledged' (controllers already use it).
        DB::statement("ALTER TABLE appraisals MODIFY COLUMN status ENUM('draft','finalized','shared','acknowledged') NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        Schema::table('appraisals', function (Blueprint $table) {
            if (Schema::hasColumn('appraisals', 'effective_from')) {
                $table->dropIndex(['effective_from']);
                $table->dropColumn('effective_from');
            }
        });

        DB::statement("ALTER TABLE appraisals MODIFY COLUMN status ENUM('draft','finalized','shared') NOT NULL DEFAULT 'draft'");
    }
};
