<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Payment receipt / proof-of-transfer attachment (bank slip, cheque scan,
 * UPI screenshot). Stored on the `public` disk like expense receipts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('attachment')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('attachment');
        });
    }
};
