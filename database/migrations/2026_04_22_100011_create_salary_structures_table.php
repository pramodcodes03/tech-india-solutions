<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();

            // CTC (monthly amounts; annual = ×12)
            $table->decimal('basic', 12, 2)->default(0);
            $table->decimal('hra', 12, 2)->default(0);
            $table->decimal('conveyance', 12, 2)->default(0);
            $table->decimal('medical', 12, 2)->default(0);
            $table->decimal('special', 12, 2)->default(0);
            $table->decimal('other_allowance', 12, 2)->default(0);
            $table->decimal('gross_monthly', 12, 2)->default(0);
            $table->decimal('ctc_annual', 14, 2)->default(0);

            // Deductions (employee share)
            $table->decimal('pf_percent', 5, 2)->default(12.00);
            $table->decimal('esi_percent', 5, 2)->default(0.75);
            $table->decimal('professional_tax', 10, 2)->default(0);

            // TDS
            $table->decimal('monthly_tds', 12, 2)->default(0);

            $table->boolean('is_current')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'is_current']);
            $table->index('effective_from');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_structures');
    }
};
