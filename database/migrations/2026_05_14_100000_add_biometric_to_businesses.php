<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('biometric_api_url')->nullable()->after('gst');
            $table->boolean('biometric_enabled')->default(false)->after('biometric_api_url');
            $table->timestamp('biometric_last_synced_at')->nullable()->after('biometric_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['biometric_api_url', 'biometric_enabled', 'biometric_last_synced_at']);
        });
    }
};
