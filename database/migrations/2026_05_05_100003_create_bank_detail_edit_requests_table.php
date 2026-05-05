<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Approval queue for HR-requested edits to an employee's bank account number
 * and IFSC code. Once an employee record is saved, those two fields become
 * read-only for HR — to change them, HR submits a request here, and an
 * Admin / Super Admin approves it (which applies the new values to the
 * employee record) or rejects it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_detail_edit_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('admins')->cascadeOnDelete();

            // Snapshot of current values at request time (audit trail).
            $table->string('current_account_number')->nullable();
            $table->string('current_ifsc', 20)->nullable();
            $table->string('current_bank_name')->nullable();
            $table->string('current_bank_branch')->nullable();

            // Proposed new values (optional individually — HR may only change one).
            $table->string('requested_account_number')->nullable();
            $table->string('requested_ifsc', 20)->nullable();
            $table->string('requested_bank_name')->nullable();
            $table->string('requested_bank_branch')->nullable();

            $table->text('reason');

            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();

            $table->timestamps();

            $table->index(['business_id', 'status']);
            $table->index(['employee_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_detail_edit_requests');
    }
};
