<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Comp-off can now be approved by the employee's reporting manager (from the
 * employee portal), mirroring leave approvals. Track that manager separately
 * from the admin approver.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comp_off_requests', function (Blueprint $table) {
            $table->foreignId('approved_by_employee_id')->nullable()->after('approved_by')
                ->constrained('employees')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('comp_off_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by_employee_id');
        });
    }
};
