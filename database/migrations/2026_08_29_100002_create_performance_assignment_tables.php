<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module A, part 2 of 4: goals as actually assigned to a person for a cycle.
 *
 * The master (kras / kpis) is the template; these are the live instances an
 * employee is scored against. Weightage is carried here as well as on the
 * master so it can be tuned per department or per employee without touching
 * the template — which is what "formula and weightages fully configurable
 * per department or per individual employee" means in practice.
 *
 * The per-KRA self and manager ratings live here rather than in a separate
 * table because they are one row per assigned goal by definition; the
 * cycle-level narrative (achievements, feedback, moderation) has its own
 * tables in part 3.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_kras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('performance_cycle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('kra_id')->constrained('kras')->cascadeOnDelete();

            // Must total 100 across an employee's KRAs in a cycle — enforced by
            // the weightage validator on the assignment screen.
            $table->decimal('weightage', 5, 2)->default(0);
            $table->foreignId('manager_id')->nullable()->constrained('employees')->nullOnDelete();

            // assigned → self_submitted → manager_reviewed → hr_reviewed → finalized
            // (sent_back returns it to the previous stage)
            $table->string('status', 20)->default('assigned');

            $table->decimal('self_rating', 3, 1)->nullable();      // 1–5
            $table->text('self_remarks')->nullable();
            $table->decimal('manager_rating', 3, 1)->nullable();   // 1–5
            $table->text('manager_feedback')->nullable();

            // 0–100 per stage, rolled up from the KPIs underneath.
            $table->decimal('self_score', 5, 2)->nullable();
            $table->decimal('manager_score', 5, 2)->nullable();
            $table->decimal('hr_score', 5, 2)->nullable();
            $table->decimal('final_score', 5, 2)->nullable();

            $table->foreignId('assigned_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();

            $table->unique(['performance_cycle_id', 'employee_id', 'kra_id'], 'employee_kras_cycle_employee_kra_unique');
            $table->index(['business_id', 'employee_id', 'status']);
            $table->index(['business_id', 'manager_id']);
        });

        Schema::create('employee_kpis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_kra_id')->constrained()->cascadeOnDelete();
            $table->foreignId('kpi_id')->constrained('kpis')->cascadeOnDelete();

            // Copied from the master at assignment so a later edit to the
            // template never silently rewrites a cycle already under review.
            $table->decimal('target_value', 14, 2)->default(0);
            $table->decimal('achieved_value', 14, 2)->nullable();
            $table->decimal('weightage', 5, 2)->default(0);
            $table->string('score_formula', 20)->default('higher_better');
            $table->decimal('score', 5, 2)->nullable();          // 0–100, derived
            $table->string('remarks', 500)->nullable();
            $table->timestamps();

            $table->unique(['employee_kra_id', 'kpi_id']);
            $table->index(['business_id', 'employee_kra_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_kpis');
        Schema::dropIfExists('employee_kras');
    }
};
