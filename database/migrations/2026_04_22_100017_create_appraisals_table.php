<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appraisals', function (Blueprint $table) {
            $table->id();
            $table->string('appraisal_code')->unique();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('cycle'); // e.g. "H1-2026" or "Annual-2026"
            $table->date('period_start');
            $table->date('period_end');

            // Scoring (0-100)
            $table->decimal('performance_score', 5, 2)->default(0);
            $table->decimal('attendance_score', 5, 2)->default(0);
            $table->decimal('leave_score', 5, 2)->default(0);
            $table->decimal('penalty_score', 5, 2)->default(0);
            $table->decimal('warning_score', 5, 2)->default(0);
            $table->decimal('overall_score', 5, 2)->default(0);
            $table->string('rating', 30)->nullable(); // Outstanding / Exceeds / Meets / Below / Poor

            // Auto-computed snapshot counters
            $table->unsignedInteger('present_days')->default(0);
            $table->unsignedInteger('absent_days')->default(0);
            $table->decimal('leave_days', 6, 2)->default(0);
            $table->unsignedInteger('penalty_count')->default(0);
            $table->decimal('penalty_total', 12, 2)->default(0);
            $table->unsignedInteger('warning_count')->default(0);

            $table->text('strengths')->nullable();
            $table->text('improvement_areas')->nullable();
            $table->text('manager_comments')->nullable();
            $table->text('employee_comments')->nullable();

            // Outcome
            $table->decimal('recommended_hike_percent', 5, 2)->nullable();
            $table->decimal('new_ctc_annual', 14, 2)->nullable();

            $table->enum('status', ['draft', 'finalized', 'shared'])->default('draft');
            $table->foreignId('conducted_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'cycle']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appraisals');
    }
};
