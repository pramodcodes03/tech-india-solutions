<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Employee self-service attendance regularization (missed / wrong punch).
 * Structured request → HR review → resolution, with a configurable TAT
 * (default 48h) tracked via sla_due_at + escalated flag.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_regularizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            // The attendance row being corrected (may be null for a fully missed day).
            $table->foreignId('attendance_id')->nullable()->constrained('attendances')->nullOnDelete();
            $table->date('date');
            $table->enum('request_type', ['missed_punch', 'wrong_punch', 'forgot_checkout', 'missed_day', 'other'])->default('missed_punch');
            $table->time('expected_in')->nullable();
            $table->time('expected_out')->nullable();
            $table->text('reason');

            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_remarks')->nullable();

            // TAT / SLA tracking.
            $table->timestamp('sla_due_at')->nullable();
            $table->boolean('escalated')->default(false);
            $table->boolean('applied')->default(false); // whether the correction was written to attendance

            $table->timestamps();

            $table->index(['business_id', 'status']);
            $table->index(['employee_id', 'date']);
            $table->index(['status', 'sla_due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_regularizations');
    }
};
