<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Leaves can now be approved by a Department Head (an Employee) from
        // the employee portal — not just by an Admin. `approver_id` is FK to
        // admins, so manager (employee) approvals are tracked here instead.
        // Exactly one of approver_id / approver_employee_id is set per action.
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->foreignId('approver_employee_id')->nullable()->after('approver_id')
                ->constrained('employees')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approver_employee_id');
        });
    }
};
