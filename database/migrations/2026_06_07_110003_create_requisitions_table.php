<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requisitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('requisition_code', 40);
            $table->foreignId('requested_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->enum('category', ['furniture', 'chairs', 'systems', 'it_equipment', 'stationery', 'other'])->default('other');
            $table->string('title', 160);
            $table->text('purpose')->nullable();
            $table->decimal('requested_amount', 14, 2)->default(0);
            $table->decimal('estimated_amount', 14, 2)->nullable();

            // pending → approved → disbursed / rejected. current_level walks the chain.
            $table->enum('status', ['pending', 'approved', 'rejected', 'disbursed'])->default('pending');
            $table->unsignedInteger('current_level')->default(1);
            $table->timestamp('disbursed_at')->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'status']);
            $table->index('category');
        });

        Schema::create('requisition_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requisition_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('level');
            $table->string('approver_role')->nullable(); // role expected to act at this level
            $table->foreignId('approver_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('remarks')->nullable();
            $table->timestamp('actioned_at')->nullable();
            $table->timestamps();

            $table->index(['requisition_id', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requisition_approvals');
        Schema::dropIfExists('requisitions');
    }
};
