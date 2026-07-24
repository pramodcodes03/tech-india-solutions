<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Employee "Utilize Budget" expenses are linked back to the budget they
        // were spent against and to the submitting employee, so utilisation =
        // sum of expenses under that budget (per the feature spec).
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('expense_budget_id')->nullable()->after('expense_subcategory_id')
                ->constrained('expense_budgets')->nullOnDelete();
            $table->foreignId('submitted_by_employee_id')->nullable()->after('expense_budget_id')
                ->constrained('employees')->nullOnDelete();
            $table->index(['business_id', 'expense_budget_id']);
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex(['business_id', 'expense_budget_id']);
            $table->dropConstrainedForeignId('submitted_by_employee_id');
            $table->dropConstrainedForeignId('expense_budget_id');
        });
    }
};
