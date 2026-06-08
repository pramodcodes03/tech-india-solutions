<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Idempotency + audit ledger for automated leave accrual and year-end
 * lapse / carry-forward. The unique (employee, type, period_key) guarantees
 * a daily cron never double-credits the same accrual period.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_accrual_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            // e.g. 2026-06 (monthly), 2026-H1 (half-yearly), 2026 (annual),
            // 2026-LAPSE / 2026-CF (year-end events).
            $table->string('period_key', 20);
            $table->enum('event', ['accrual', 'lapse', 'carry_forward'])->default('accrual');
            $table->decimal('amount', 6, 2)->default(0);
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'leave_type_id', 'period_key', 'event'], 'lal_unique');
            $table->index(['business_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_accrual_logs');
    }
};
