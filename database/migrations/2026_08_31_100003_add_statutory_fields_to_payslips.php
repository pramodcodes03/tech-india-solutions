<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Payslip columns the wage registers print but payroll never stored.
 *
 * Form D wants arrears carried from last month and the LWF deduction as its own
 * column; Form IV and Form B want overtime split out from other allowances; and
 * Form B prints the employer's PF share beside the employee's.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->decimal('arrears', 12, 2)->default(0)->after('other_allowance');
            $table->decimal('overtime_hours', 7, 2)->default(0)->after('arrears');
            $table->decimal('overtime_amount', 12, 2)->default(0)->after('overtime_hours');
            $table->decimal('lwf', 12, 2)->default(0)->after('professional_tax');
            $table->decimal('employer_pf', 12, 2)->default(0)->after('lwf');
        });
    }

    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->dropColumn(['arrears', 'overtime_hours', 'overtime_amount', 'lwf', 'employer_pf']);
        });
    }
};
