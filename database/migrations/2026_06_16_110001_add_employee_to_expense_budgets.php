<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A budget can now optionally be SANCTIONED to a specific employee
        // (e.g. ₹50,000 to an IT/Marketing staffer). Nullable, so existing
        // category-wide budgets keep working unchanged. When set, utilisation
        // is computed from that employee's own approved reimbursement claims.
        Schema::table('expense_budgets', function (Blueprint $table) {
            $table->foreignId('employee_id')->nullable()->after('expense_category_id')
                ->constrained('employees')->nullOnDelete();
            $table->index(['business_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::table('expense_budgets', function (Blueprint $table) {
            $table->dropIndex(['business_id', 'employee_id']);
            $table->dropConstrainedForeignId('employee_id');
        });
    }
};
