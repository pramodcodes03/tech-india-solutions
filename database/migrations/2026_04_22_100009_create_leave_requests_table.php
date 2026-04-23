<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_code')->unique();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types')->restrictOnDelete();
            $table->date('from_date');
            $table->date('to_date');
            $table->decimal('days', 5, 1);
            $table->enum('day_portion', ['full', 'first_half', 'second_half'])->default('full');
            $table->text('reason');
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->foreignId('approver_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('actioned_at')->nullable();
            $table->text('approver_remarks')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'status']);
            $table->index(['from_date', 'to_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
