<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module A, part 4 of 4: the finalised score for one employee in one cycle,
 * and the reward it recommends.
 *
 * Stored rather than recomputed on read so a finalised cycle keeps the number
 * it was signed off on, even after weightages or band thresholds are changed
 * for the next cycle.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('performance_cycle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();

            $table->decimal('self_score', 5, 2)->nullable();
            $table->decimal('manager_score', 5, 2)->nullable();
            $table->decimal('final_score', 5, 2)->nullable();       // 0–100, weighted across KRAs

            $table->foreignId('performance_band_id')->nullable()->constrained()->nullOnDelete();
            $table->string('band_name', 60)->nullable();            // snapshot of the band label
            // Where the bell curve placed them, when it is switched on. Kept
            // separate from band_name so you can always see the raw band too.
            $table->string('bell_curve_band', 60)->nullable();
            $table->unsignedInteger('rank_in_business')->nullable();

            // increment|bonus|promotion|training|pip|none
            $table->string('recommendation', 20)->default('none');
            $table->string('recommendation_status', 20)->default('suggested'); // suggested|accepted|overridden|rejected
            $table->string('recommendation_override', 20)->nullable();
            $table->string('recommendation_notes', 500)->nullable();
            $table->foreignId('appraisal_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamp('computed_at')->nullable();
            $table->foreignId('finalized_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->unique(['performance_cycle_id', 'employee_id'], 'perf_scores_cycle_employee_unique');
            $table->index(['business_id', 'final_score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_scores');
    }
};
