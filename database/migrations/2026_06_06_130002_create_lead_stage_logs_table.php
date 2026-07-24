<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stage-transition log per lead. Captures the assigned employee's remark at
 * each move and the time spent in the previous stage (seconds) so we can
 * report time-per-stage and total lead age.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_stage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('remarks')->nullable();
            // Seconds spent in from_status before this transition.
            $table->unsignedBigInteger('duration_seconds')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index('lead_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_stage_logs');
    }
};
