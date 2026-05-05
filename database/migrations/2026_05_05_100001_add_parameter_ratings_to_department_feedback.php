<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('department_feedback', function (Blueprint $table) {
            // 10-parameter scores stored as JSON: { service_quality: 4, timeliness: 3, ... }
            // Each value is 0-5 (0 = N/A). Old rows have null here; old `rating` is preserved
            // and shown as the legacy single score.
            $table->json('parameter_ratings')->nullable()->after('rating');
            // Average of the 10 parameter scores (excluding 0s which mean "N/A"). Cached so
            // listing/aggregation queries don't have to recompute from JSON.
            $table->decimal('overall_rating', 4, 2)->nullable()->after('parameter_ratings');
        });
    }

    public function down(): void
    {
        Schema::table('department_feedback', function (Blueprint $table) {
            $table->dropColumn(['parameter_ratings', 'overall_rating']);
        });
    }
};
