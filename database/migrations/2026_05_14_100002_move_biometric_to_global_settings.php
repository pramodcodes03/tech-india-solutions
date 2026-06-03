<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Biometric config used to live as columns on `businesses` — implying a
 * different device per tenant. The real deployment is the opposite: one shared
 * device API, and employee codes are globally unique. So we move config to the
 * global settings table and drop the per-business columns + the per-business
 * scoping on the sync log.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1) Hoist any existing per-business config into global settings.
        $existing = DB::table('businesses')
            ->whereNotNull('biometric_api_url')
            ->where('biometric_enabled', true)
            ->orderBy('id')
            ->first(['biometric_api_url']);

        if ($existing && ! Setting::where('key', 'biometric_api_url')->exists()) {
            Setting::create([
                'key'   => 'biometric_api_url',
                'value' => $existing->biometric_api_url,
                'group' => 'biometric',
            ]);
            Setting::create([
                'key'   => 'biometric_enabled',
                'value' => '1',
                'group' => 'biometric',
            ]);
        }

        // 2) Drop the per-business columns. They no longer make sense.
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['biometric_api_url', 'biometric_enabled', 'biometric_last_synced_at']);
        });

        // 3) Sync logs are now global runs — make business_id nullable + drop the
        //    NOT NULL constraint. Existing rows keep their business_id; new global
        //    runs will write NULL.
        Schema::table('biometric_sync_logs', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
        });
        Schema::table('biometric_sync_logs', function (Blueprint $table) {
            $table->foreignId('business_id')->nullable()->change();
            $table->foreign('business_id')->references('id')->on('businesses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('biometric_sync_logs', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
        });
        Schema::table('biometric_sync_logs', function (Blueprint $table) {
            $table->foreignId('business_id')->nullable(false)->change();
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->string('biometric_api_url')->nullable();
            $table->boolean('biometric_enabled')->default(false);
            $table->timestamp('biometric_last_synced_at')->nullable();
        });

        Setting::whereIn('key', ['biometric_api_url', 'biometric_enabled', 'biometric_last_synced_at'])->delete();
    }
};
