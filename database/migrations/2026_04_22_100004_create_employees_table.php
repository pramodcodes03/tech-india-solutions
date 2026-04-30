<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('employee_code');

            // Auth
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->timestamp('last_login_at')->nullable();

            // Personal
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('alt_phone')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed'])->nullable();
            $table->string('blood_group', 5)->nullable();
            $table->string('profile_photo')->nullable();

            // Address
            $table->text('current_address')->nullable();
            $table->text('permanent_address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('pincode', 10)->nullable();
            $table->string('country')->default('India');

            // Employment
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('designation_id')->nullable()->constrained('designations')->nullOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            $table->foreignId('reporting_manager_id')->nullable();
            $table->date('joining_date')->nullable();
            $table->date('probation_end_date')->nullable();
            $table->date('confirmation_date')->nullable();
            $table->date('resignation_date')->nullable();
            $table->date('last_working_date')->nullable();
            $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'intern'])->default('full_time');
            $table->enum('work_mode', ['on_site', 'remote', 'hybrid'])->default('on_site');

            // Govt / statutory
            $table->string('pan_number', 20)->nullable();
            $table->string('aadhar_number', 20)->nullable();
            $table->string('pf_number', 30)->nullable();
            $table->string('uan_number', 30)->nullable();
            $table->string('esi_number', 30)->nullable();

            // Bank
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_ifsc', 20)->nullable();
            $table->string('bank_branch')->nullable();

            // Emergency contact
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_relation')->nullable();
            $table->string('emergency_contact_phone')->nullable();

            // Background verification
            $table->enum('bgv_status', ['pending', 'in_progress', 'cleared', 'failed'])->default('pending');
            $table->date('bgv_completed_at')->nullable();
            $table->text('bgv_notes')->nullable();

            // Lifecycle
            $table->enum('status', ['active', 'probation', 'on_notice', 'terminated', 'resigned', 'absconded', 'inactive'])->default('active');

            // Audit
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['business_id', 'employee_code']);

            $table->index('status');
            $table->index('department_id');
            $table->index('designation_id');
            $table->index('joining_date');
            $table->index('date_of_birth');
        });

        // Add FKs that reference employees (self-ref + departments.head_id)
        Schema::table('employees', function (Blueprint $table) {
            $table->foreign('reporting_manager_id')->references('id')->on('employees')->nullOnDelete();
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->foreign('head_id')->references('id')->on('employees')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign(['head_id']);
        });
        Schema::dropIfExists('employees');
    }
};
