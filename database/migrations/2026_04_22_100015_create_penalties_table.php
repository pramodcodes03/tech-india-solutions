<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penalties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('penalty_code');
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('penalty_type_id')->constrained('penalty_types')->restrictOnDelete();
            $table->decimal('amount', 10, 2);
            $table->date('incident_date');
            $table->text('remarks')->nullable();

            // PIP / reduction logic
            $table->enum('status', ['pending', 'deducted', 'waived', 'reduced'])->default('pending');
            $table->decimal('original_amount', 10, 2); // snapshot before reduction
            $table->date('eligible_reduction_after')->nullable(); // 5 months from incident
            $table->decimal('reduced_amount', 10, 2)->nullable();
            $table->date('reduced_on')->nullable();
            $table->foreignId('reduced_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->text('reduction_reason')->nullable();

            // Link to payslip when deducted
            $table->foreignId('payslip_id')->nullable()->constrained('payslips')->nullOnDelete();

            $table->foreignId('issued_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'status']);
            $table->index('incident_date');
            $table->unique(['business_id', 'penalty_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penalties');
    }
};
