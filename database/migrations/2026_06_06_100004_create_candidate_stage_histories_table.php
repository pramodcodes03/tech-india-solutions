<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit trail of every pipeline move for a candidate. Drives the candidate
 * timeline and the stage-wise funnel / time-in-stage reports.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_stage_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_stage_id')->nullable()->constrained('recruitment_stages')->nullOnDelete();
            $table->foreignId('to_stage_id')->nullable()->constrained('recruitment_stages')->nullOnDelete();
            $table->string('action', 40)->default('moved'); // moved, created, hired, rejected, withdrawn, note
            $table->text('remarks')->nullable();
            $table->foreignId('moved_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index('candidate_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_stage_histories');
    }
};
