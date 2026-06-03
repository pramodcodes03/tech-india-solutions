<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('biometric_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->date('sync_date');
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->enum('status', ['running', 'success', 'failed', 'partial'])->default('running');
            $table->unsignedInteger('punches_fetched')->default(0);
            $table->unsignedInteger('employees_matched')->default(0);
            $table->unsignedInteger('attendance_upserts')->default(0);
            $table->unsignedInteger('unmatched_cards')->default(0);
            $table->json('unmatched_card_list')->nullable();
            $table->text('error_message')->nullable();
            // 'scheduled' for cron, 'manual:<admin_id>' for UI button
            $table->string('triggered_by', 64)->default('scheduled');
            $table->timestamps();

            $table->index(['business_id', 'sync_date']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biometric_sync_logs');
    }
};
