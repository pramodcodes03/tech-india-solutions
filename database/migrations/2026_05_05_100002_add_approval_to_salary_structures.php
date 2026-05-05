<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds an approval workflow to salary_structures. New rows submitted by HR
 * default to status='pending' and are NOT used by payroll until an Admin /
 * Super Admin approves them. The `is_current` flag is now driven by the
 * combination of (status = 'approved' AND most-recent for the employee).
 *
 * For backwards-compat: any rows that exist today (created before this
 * migration) are marked status='approved' so payroll keeps working.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_structures', function (Blueprint $table) {
            $table->enum('status', ['pending', 'approved', 'rejected'])
                ->default('pending')
                ->after('is_current');
            $table->foreignId('submitted_by')->nullable()->after('status')
                ->constrained('admins')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable()->after('submitted_by');
            $table->foreignId('reviewed_by')->nullable()->after('submitted_at')
                ->constrained('admins')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->text('review_notes')->nullable()->after('reviewed_at');

            $table->index(['business_id', 'status']);
        });

        // Approve any existing rows so payroll continues to work.
        \Illuminate\Support\Facades\DB::table('salary_structures')->update([
            'status' => 'approved',
            'reviewed_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('salary_structures', function (Blueprint $table) {
            $table->dropIndex(['business_id', 'status']);
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropConstrainedForeignId('submitted_by');
            $table->dropColumn(['status', 'submitted_at', 'reviewed_at', 'review_notes']);
        });
    }
};
