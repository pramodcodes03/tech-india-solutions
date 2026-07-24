<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reimbursement_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('claim_code', 40);
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('expense_category_id')->nullable()->constrained('expense_categories')->nullOnDelete();
            $table->string('title', 160);
            $table->text('purpose')->nullable();
            $table->decimal('amount', 12, 2);
            $table->date('claim_date');
            $table->string('bill_path')->nullable();

            // submitted → under_review → approved → disbursed / rejected
            $table->enum('status', ['submitted', 'under_review', 'approved', 'disbursed', 'rejected'])->default('submitted');
            $table->foreignId('reviewed_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_remarks')->nullable();
            $table->decimal('approved_amount', 12, 2)->nullable();
            $table->timestamp('disbursed_at')->nullable();
            $table->string('payment_reference')->nullable();

            $table->timestamps();
            $table->index(['business_id', 'status']);
            $table->index(['employee_id', 'status']);
        });

        Schema::create('reimbursement_claim_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reimbursement_claim_id')->constrained()->cascadeOnDelete();
            $table->string('status', 30);
            $table->string('remarks')->nullable();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamps();
            $table->index('reimbursement_claim_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reimbursement_claim_logs');
        Schema::dropIfExists('reimbursement_claims');
    }
};
