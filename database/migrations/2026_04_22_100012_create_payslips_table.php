<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->string('payslip_code')->unique();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->unsignedSmallInteger('month'); // 1-12
            $table->unsignedSmallInteger('year');
            $table->date('period_start');
            $table->date('period_end');

            // Days
            $table->unsignedSmallInteger('working_days')->default(0);
            $table->decimal('paid_days', 5, 1)->default(0);
            $table->decimal('lop_days', 5, 1)->default(0); // loss of pay

            // Earnings
            $table->decimal('basic', 12, 2)->default(0);
            $table->decimal('hra', 12, 2)->default(0);
            $table->decimal('conveyance', 12, 2)->default(0);
            $table->decimal('medical', 12, 2)->default(0);
            $table->decimal('special', 12, 2)->default(0);
            $table->decimal('other_allowance', 12, 2)->default(0);
            $table->decimal('bonus', 12, 2)->default(0);
            $table->decimal('gross_earnings', 12, 2)->default(0);

            // Deductions
            $table->decimal('pf', 12, 2)->default(0);
            $table->decimal('esi', 12, 2)->default(0);
            $table->decimal('professional_tax', 12, 2)->default(0);
            $table->decimal('tds', 12, 2)->default(0);
            $table->decimal('penalty_deduction', 12, 2)->default(0);
            $table->decimal('lop_deduction', 12, 2)->default(0);
            $table->decimal('other_deductions', 12, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);

            // Net
            $table->decimal('net_pay', 12, 2)->default(0);

            $table->enum('status', ['draft', 'generated', 'paid'])->default('draft');
            $table->date('paid_on')->nullable();
            $table->string('payment_reference')->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('generated_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->unique(['employee_id', 'month', 'year']);
            $table->index(['year', 'month']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslips');
    }
};
