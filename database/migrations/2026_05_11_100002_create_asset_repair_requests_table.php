<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_repair_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();

            // Request code — unique per business
            $table->string('request_code');

            // Asset details
            $table->foreignId('asset_id')->constrained('assets')->restrictOnDelete();
            $table->string('asset_type')->nullable(); // free-text asset type / category label

            // Repair details
            $table->string('vendor_name');
            $table->date('repair_delivery_date');
            $table->text('description');
            $table->decimal('estimated_cost', 15, 2)->nullable();

            // Primary approval
            $table->string('status')->default('pending');
            // pending | approved | rejected | cost_approval_pending | cost_approved | cost_rejected

            $table->foreignId('requested_by')->constrained('admins')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_remarks')->nullable();

            // Costing approval (raised on same ticket after repair is done)
            $table->decimal('costing_requested_amount', 15, 2)->nullable();
            $table->text('costing_description')->nullable();
            $table->string('costing_status')->nullable();
            // pending | approved | rejected
            $table->foreignId('costing_approved_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('costing_approved_at')->nullable();
            $table->text('costing_remarks')->nullable();

            $table->timestamps();

            $table->unique(['business_id', 'request_code']);
            $table->index(['business_id', 'status']);
            $table->index('asset_id');
            $table->index('requested_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_repair_requests');
    }
};
