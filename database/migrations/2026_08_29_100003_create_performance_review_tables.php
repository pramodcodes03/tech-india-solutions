<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module A, part 3 of 4: the three assessment stages, plus the evidence,
 * feedback and audit trail that hang off them.
 *
 * Escalation is Employee → Manager → HR → Admin. Each stage gets its own table
 * because each asks for genuinely different things, and because a send-back has
 * to be able to reopen one stage without disturbing the others.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_self_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('performance_cycle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->text('achievements')->nullable();
            $table->text('challenges')->nullable();
            $table->text('learnings')->nullable();
            $table->text('future_goals')->nullable();
            $table->text('comments')->nullable();
            $table->string('status', 20)->default('draft');     // draft|submitted|sent_back
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['performance_cycle_id', 'employee_id'], 'self_reviews_cycle_employee_unique');
        });

        Schema::create('performance_manager_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('performance_cycle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            // A manager is normally an employee (department head); an admin can
            // stand in, so both are recorded and either may be null.
            $table->foreignId('reviewer_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('reviewer_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->decimal('overall_rating', 3, 1)->nullable();  // 1–5
            $table->text('feedback')->nullable();
            $table->text('suggestions')->nullable();
            $table->text('comments')->nullable();
            $table->boolean('recommend_promotion')->default(false);
            $table->boolean('recommend_training')->default(false);
            $table->string('training_notes', 500)->nullable();
            $table->string('status', 20)->default('pending');     // pending|submitted|sent_back
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['performance_cycle_id', 'employee_id'], 'manager_reviews_cycle_employee_unique');
        });

        Schema::create('performance_hr_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('performance_cycle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            // HR may moderate a manager rating that is out of line with the
            // department; the original is never overwritten, it is recorded here.
            $table->decimal('moderated_rating', 3, 1)->nullable();
            $table->string('moderation_reason', 500)->nullable();
            // Snapshot of the discipline data the score was verified against, so
            // the finalised review still makes sense a year later.
            $table->decimal('attendance_percent', 5, 2)->nullable();
            $table->unsignedSmallInteger('penalty_count')->default(0);
            $table->unsignedSmallInteger('warning_count')->default(0);
            $table->text('comments')->nullable();
            $table->string('status', 20)->default('pending');     // pending|finalized|sent_back
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();

            $table->unique(['performance_cycle_id', 'employee_id'], 'hr_reviews_cycle_employee_unique');
        });

        Schema::create('performance_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('performance_cycle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('author_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('stage', 20);                          // self|manager|hr|admin
            $table->text('body');
            // Private notes stay between reviewers; the employee never sees them.
            $table->boolean('is_private')->default(false);
            $table->timestamps();

            $table->index(['business_id', 'performance_cycle_id', 'employee_id'], 'perf_feedback_cycle_employee_index');
        });

        Schema::create('performance_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('performance_cycle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            // Evidence is normally attached to one KRA; null = general to the cycle.
            $table->foreignId('employee_kra_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->string('original_name', 255);
            $table->string('mime_type', 100)->nullable();
            $table->unsignedInteger('size_bytes')->default(0);
            $table->foreignId('uploaded_by_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('uploaded_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index(['business_id', 'performance_cycle_id', 'employee_id'], 'perf_docs_cycle_employee_index');
        });

        Schema::create('performance_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('performance_cycle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('stage', 20);                          // self|manager|hr|admin
            $table->string('action', 30);                         // assigned|submitted|reviewed|sent_back|finalized
            $table->foreignId('actor_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('actor_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('remarks', 500)->nullable();
            $table->timestamps();

            $table->index(['business_id', 'performance_cycle_id', 'employee_id'], 'perf_history_cycle_employee_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_histories');
        Schema::dropIfExists('performance_documents');
        Schema::dropIfExists('performance_feedback');
        Schema::dropIfExists('performance_hr_reviews');
        Schema::dropIfExists('performance_manager_reviews');
        Schema::dropIfExists('performance_self_reviews');
    }
};
