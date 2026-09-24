<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Combined Leave: one leave request funded from more than one leave type.
 *
 * The client's case is fractional balances — 0.5 Casual + 0.5 Sick makes one
 * full day off, instead of the employee being told they have "no full day
 * anywhere". Each contributing type gets a row here carrying the days it funds,
 * and the paid/unpaid split HR settles at approval.
 *
 * `leave_requests.leave_type_id` is left in place and set to the largest
 * contributor, so every existing screen, report and payroll path that reads a
 * single type keeps working; `is_combined` tells the UI when to show the split
 * instead of the single type.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_request_splits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained()->cascadeOnDelete();
            $table->decimal('days', 4, 1);
            $table->decimal('paid_days', 4, 1)->default(0);
            $table->decimal('unpaid_days', 4, 1)->default(0);
            $table->timestamps();

            $table->unique(['leave_request_id', 'leave_type_id']);
            $table->index(['business_id', 'leave_type_id']);
        });

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->boolean('is_combined')->default(false)->after('leave_type_id');
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn('is_combined');
        });

        Schema::dropIfExists('leave_request_splits');
    }
};
