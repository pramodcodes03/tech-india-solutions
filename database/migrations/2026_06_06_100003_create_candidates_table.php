<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('candidate_code', 40)->nullable();

            // Profile
            $table->string('first_name', 80);
            $table->string('last_name', 80)->nullable();
            $table->string('email', 160)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('current_location', 120)->nullable();
            $table->decimal('total_experience', 5, 1)->nullable(); // years
            $table->decimal('current_ctc', 12, 2)->nullable();
            $table->decimal('expected_ctc', 12, 2)->nullable();
            $table->unsignedInteger('notice_period_days')->nullable();
            $table->string('resume_path')->nullable();

            // Source: walk-in, referral, campus, online, agency, other.
            $table->enum('source', ['walkin', 'referral', 'campus', 'online', 'agency', 'other'])->default('other');
            // Referral tracking — links to the employee who referred this candidate.
            $table->foreignId('referred_by_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            // Campus batch (only for source = campus).
            $table->foreignId('batch_id')->nullable()->constrained('recruitment_batches')->nullOnDelete();

            // Role being hired for.
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('designation_id')->nullable()->constrained('designations')->nullOnDelete();

            // Pipeline.
            $table->foreignId('stage_id')->nullable()->constrained('recruitment_stages')->nullOnDelete();
            // active = in pipeline, hired / rejected / withdrawn = closed.
            $table->enum('status', ['active', 'hired', 'rejected', 'withdrawn'])->default('active');
            $table->date('applied_at')->nullable();
            $table->timestamp('stage_changed_at')->nullable();
            $table->timestamp('hired_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->string('rejection_reason')->nullable();

            // Offer details (used by the offer-letter PDF).
            $table->decimal('offer_ctc', 12, 2)->nullable();
            $table->string('offer_designation', 120)->nullable();
            $table->date('offer_date')->nullable();
            $table->date('proposed_joining_date')->nullable();
            $table->timestamp('offer_generated_at')->nullable();

            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['business_id', 'status']);
            $table->index(['business_id', 'source']);
            $table->index(['business_id', 'stage_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
