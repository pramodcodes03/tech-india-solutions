<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optional per-employee monthly accrual rate override. When set, this employee
 * accrues this leave type at the given rate instead of the leave type's default
 * rate — so specific employees can be on, say, 1.0/month while everyone else
 * stays on 0.5/month. NULL = use the leave type's default rate.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_balances', function (Blueprint $table) {
            $table->decimal('accrual_rate', 5, 2)->nullable()->after('carried_forward');
        });
    }

    public function down(): void
    {
        Schema::table('leave_balances', function (Blueprint $table) {
            $table->dropColumn('accrual_rate');
        });
    }
};
