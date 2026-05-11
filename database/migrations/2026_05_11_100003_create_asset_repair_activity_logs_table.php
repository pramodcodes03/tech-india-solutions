<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_repair_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_repair_request_id')->constrained('asset_repair_requests')->cascadeOnDelete();

            // Who performed the action
            $table->foreignId('performed_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('performed_by_name'); // snapshot — never loses the name even if admin deleted

            // Event
            $table->string('event');
            // request_raised | approved | rejected | cost_approval_raised
            // cost_approved  | cost_rejected | remarked

            $table->text('remarks')->nullable();

            // Snapshot of status at the time of the event
            $table->string('status_snapshot')->nullable();
            $table->string('costing_status_snapshot')->nullable();

            $table->timestamp('performed_at');

            $table->index(['asset_repair_request_id']);
            $table->index(['business_id', 'performed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_repair_activity_logs');
    }
};
