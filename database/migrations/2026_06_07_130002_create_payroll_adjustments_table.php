<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-month, per-employee pay overrides — incentive / variable pay, arrears,
 * one-off bonus, and extra deductions — applied at payroll-run time without
 * creating a new salary-structure version.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->enum('component', ['incentive', 'arrears', 'bonus', 'extra_deduction']);
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('note')->nullable();
            $table->foreignId('payslip_id')->nullable()->constrained('payslips')->nullOnDelete();
            $table->boolean('applied')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'month', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_adjustments');
    }
};
