<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            // How the approved request is split. For pending/rejected/cancelled these stay 0.
            $table->decimal('paid_days', 5, 1)->default(0)->after('days');
            $table->decimal('unpaid_days', 5, 1)->default(0)->after('paid_days');
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn(['paid_days', 'unpaid_days']);
        });
    }
};
