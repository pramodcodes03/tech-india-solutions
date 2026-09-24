<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The five event registers behind the statutory forms.
 *
 * Each of these forms lists *every* employee on the payroll for the month and
 * prints "Nil" against those with nothing to report — so the tables here hold
 * only the exceptions, and the register joins them onto the employee list at
 * render time. An empty table is a valid, and common, nil return.
 *
 *   employee_fines           Form I     · Punjab Minimum Wages Rules, 1950
 *   employee_damage_losses   Form II    · Punjab Minimum Wages Rules, 1950
 *   employee_advances        Form II-A  · Punjab Minimum Wages Rules, 1950
 *   employee_wage_deductions Form E     · Punjab Shops & Commercial Est. Rules, 1958
 *   employee_overtimes       Form IV    · Punjab Minimum Wages Rules, 1950
 *   child_labour_records     Form A     · Punjab Child Labour (P&R) Rules, 1997
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Form I · Register of Fines ───────────────────────────────────
        Schema::create('employee_fines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('offence_date');
            $table->string('offence_nature', 255);
            $table->boolean('showed_cause')->default(false);
            $table->string('cause_particulars', 255)->nullable();
            $table->decimal('wage_rate', 12, 2)->default(0);
            $table->date('fine_date');
            $table->decimal('fine_amount', 12, 2)->default(0);
            $table->date('realised_on')->nullable();
            $table->string('remarks', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index(['business_id', 'fine_date']);
            $table->index('employee_id');
        });

        // ── Form II · Register of deductions for damage or loss ──────────
        Schema::create('employee_damage_losses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('damage_date');
            $table->string('damage_description', 255);
            $table->boolean('showed_cause')->default(false);
            $table->string('cause_particulars', 255)->nullable();
            $table->date('deduction_date');
            $table->decimal('deduction_amount', 12, 2)->default(0);
            $table->unsignedSmallInteger('instalments')->default(1);
            $table->date('realised_on')->nullable();
            $table->string('remarks', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index(['business_id', 'deduction_date']);
            $table->index('employee_id');
        });

        // ── Form II-A · Register of Advances ─────────────────────────────
        Schema::create('employee_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('advance_date');
            $table->decimal('advance_amount', 12, 2)->default(0);
            $table->string('purpose', 255);
            $table->unsignedSmallInteger('instalments')->default(1);
            $table->string('postponement_grounds', 255)->nullable();
            $table->date('repaid_on')->nullable();
            $table->string('remarks', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index(['business_id', 'advance_date']);
            $table->index('employee_id');
        });

        // ── Form E · Register of Deductions ──────────────────────────────
        Schema::create('employee_wage_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('wage_period', 60)->nullable();
            $table->decimal('wages_payable', 12, 2)->default(0);
            $table->decimal('deduction_amount', 12, 2)->default(0);
            $table->date('deduction_date');
            $table->string('fault', 255);
            $table->boolean('showed_cause')->default(false);
            $table->string('purpose', 255)->nullable();
            $table->date('utilised_on')->nullable();
            $table->decimal('balance', 12, 2)->default(0);
            $table->string('remarks', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index(['business_id', 'deduction_date']);
            $table->index('employee_id');
        });

        // ── Form IV · Overtime Register ──────────────────────────────────
        Schema::create('employee_overtimes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('worked_on');
            $table->decimal('hours', 6, 2)->default(0);
            $table->decimal('normal_rate', 12, 2)->default(0);
            $table->decimal('overtime_rate', 12, 2)->default(0);
            $table->decimal('normal_earnings', 12, 2)->default(0);
            $table->decimal('overtime_earnings', 12, 2)->default(0);
            $table->date('paid_on')->nullable();
            $table->string('production_note', 120)->nullable();
            $table->string('remarks', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->unique(['employee_id', 'worked_on']);
            $table->index(['business_id', 'worked_on']);
        });

        // ── Form A · Register of Child Labour ────────────────────────────
        Schema::create('child_labour_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('child_name', 120);
            $table->string('father_name', 120)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->text('permanent_address')->nullable();
            $table->date('joined_on')->nullable();
            $table->string('nature_of_work', 150)->nullable();
            $table->string('daily_hours', 60)->nullable();
            $table->string('rest_intervals', 60)->nullable();
            $table->decimal('wages_paid', 12, 2)->default(0);
            $table->date('left_on')->nullable();
            $table->string('remarks', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index(['business_id', 'joined_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('child_labour_records');
        Schema::dropIfExists('employee_overtimes');
        Schema::dropIfExists('employee_wage_deductions');
        Schema::dropIfExists('employee_advances');
        Schema::dropIfExists('employee_damage_losses');
        Schema::dropIfExists('employee_fines');
    }
};
