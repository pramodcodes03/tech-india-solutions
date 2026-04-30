<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('expense_code'); // e.g. EXP-2026-0001
            $table->foreignId('expense_category_id')->constrained()->restrictOnDelete();
            $table->foreignId('expense_subcategory_id')->nullable()->constrained()->nullOnDelete();

            // Two types: 'recurring' (monthly fixed) or 'one_off'
            $table->enum('type', ['recurring', 'one_off'])->default('one_off');

            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('amount', 14, 2);

            // Dates
            $table->date('expense_date');                // when it occurred / was billed
            $table->date('due_date')->nullable();        // when it must be paid by
            $table->date('paid_date')->nullable();

            // Recurring fields (only used when type = recurring)
            $table->unsignedTinyInteger('due_day_of_month')->nullable(); // 1-28 (cap at 28 to avoid Feb edge case)
            $table->foreignId('recurring_template_id')->nullable()->constrained('expenses')->nullOnDelete();
            // ↑ for auto-generated monthly rows: points back to the original "template" row

            // Reminder tracking — used by SendExpenseReminders job to avoid spamming.
            $table->timestamp('last_reminder_sent_at')->nullable();
            $table->string('last_reminder_stage', 20)->nullable(); // 't-3', 't-1', 'due', 'overdue'

            // Status
            $table->enum('status', ['unpaid', 'paid', 'cancelled'])->default('unpaid');
            $table->string('payment_method', 50)->nullable(); // cash / bank / cheque / upi / card
            $table->string('payment_reference')->nullable();

            // Attachment (receipt scan)
            $table->string('attachment')->nullable();

            $table->foreignId('paid_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['business_id', 'expense_code']);
            $table->index(['business_id', 'status']);
            $table->index(['business_id', 'type']);
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
