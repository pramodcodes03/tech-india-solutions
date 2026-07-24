<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Internal employee helpdesk — distinct from the customer-facing
 * service_tickets. Supports per-department/category TAT, an escalation matrix,
 * and a full raise → assign → review → resolve → close workflow.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Categories carry the default TAT for their department.
        Schema::create('internal_ticket_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->enum('department', ['hr', 'it', 'admin', 'accounts'])->default('hr');
            $table->string('name', 100);
            $table->unsignedInteger('tat_hours')->default(48);
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->index(['business_id', 'department']);
        });

        // Configurable escalation matrix: who owns each level, after how many days.
        Schema::create('ticket_escalation_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->enum('department', ['hr', 'it', 'admin', 'accounts'])->default('hr');
            $table->unsignedInteger('level');
            $table->unsignedInteger('after_days')->default(3);
            $table->foreignId('owner_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->index(['business_id', 'department', 'level']);
        });

        Schema::create('internal_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('ticket_number', 40);
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete(); // raiser
            $table->enum('department', ['hr', 'it', 'admin', 'accounts'])->default('hr');
            $table->foreignId('category_id')->nullable()->constrained('internal_ticket_categories')->nullOnDelete();
            $table->string('subject', 160);
            $table->text('description')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['open', 'assigned', 'in_review', 'resolved', 'closed'])->default('open');
            $table->foreignId('assigned_to')->nullable()->constrained('admins')->nullOnDelete();

            // TAT + escalation tracking.
            $table->timestamp('tat_due_at')->nullable();
            $table->boolean('tat_breached')->default(false);
            $table->unsignedInteger('escalation_level')->default(0);
            $table->timestamp('escalated_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            // Source linkage (e.g. attendance-correction routed as an HR ticket).
            $table->string('source', 30)->default('self');
            $table->nullableMorphs('related');

            $table->timestamps();
            $table->index(['business_id', 'status']);
            $table->index(['department', 'status']);
            $table->index(['status', 'tat_due_at']);
        });

        Schema::create('internal_ticket_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('internal_ticket_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->foreignId('author_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('author_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->boolean('is_internal_note')->default(false); // admin-only note
            $table->timestamps();
            $table->index('internal_ticket_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internal_ticket_comments');
        Schema::dropIfExists('internal_tickets');
        Schema::dropIfExists('ticket_escalation_levels');
        Schema::dropIfExists('internal_ticket_categories');
    }
};
