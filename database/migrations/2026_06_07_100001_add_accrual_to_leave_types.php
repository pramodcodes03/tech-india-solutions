<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            // Automated accrual config — all admin-editable per leave type.
            $table->boolean('accrual_enabled')->default(false)->after('annual_quota');
            $table->decimal('accrual_rate', 5, 2)->default(0)->after('accrual_enabled'); // days credited per period
            $table->enum('accrual_frequency', ['monthly', 'half_yearly', 'annual'])->default('monthly')->after('accrual_rate');
            // Only start accruing after the employee crosses probation.
            $table->boolean('accrue_after_probation')->default(true)->after('accrual_frequency');
            // Earned-leave style rule: require N actual working days before crediting.
            $table->unsignedInteger('min_working_days')->nullable()->after('accrue_after_probation');
        });
    }

    public function down(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            $table->dropColumn(['accrual_enabled', 'accrual_rate', 'accrual_frequency', 'accrue_after_probation', 'min_working_days']);
        });
    }
};
