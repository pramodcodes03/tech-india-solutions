<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Budget top-ups: extra money added to a budget mid-period (e.g. a ₹10,000
 * monthly Google Ads budget exhausted in 10 days gets a ₹15,000 top-up).
 *
 * Stored as separate rows instead of editing expense_budgets.amount so the
 * sanctioned base and every subsequent addition stay visible — admin and
 * employee both see "Base + Top-ups = Total" with who added what and when.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_budget_topups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('expense_budget_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 14, 2);
            $table->string('note', 255)->nullable();
            $table->date('added_on');
            $table->foreignId('added_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index(['business_id', 'expense_budget_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_budget_topups');
    }
};
