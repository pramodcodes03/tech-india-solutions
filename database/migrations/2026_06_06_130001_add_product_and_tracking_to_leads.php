<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $sqlite = DB::getDriverName() === 'sqlite';

        Schema::table('leads', function (Blueprint $table) use ($sqlite) {
            // Product-wise segmentation. (FK skipped on sqlite test DB — see note.)
            $productId = $table->foreignId('product_id')->nullable()->after('source');
            if (! $sqlite) {
                $productId->constrained('products')->nullOnDelete();
            }
            // Distinct lead-received date (separate from next_follow_up_at).
            $table->date('lead_date')->nullable()->after('product_id');
            // Drives time-in-current-stage tracking.
            $table->timestamp('last_stage_changed_at')->nullable()->after('status');

            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_id');
            $table->dropColumn(['lead_date', 'last_stage_changed_at']);
        });
    }
};
