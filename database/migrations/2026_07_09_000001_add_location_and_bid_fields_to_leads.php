<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('city')->nullable()->after('company');
            $table->string('state')->nullable()->after('city');
            $table->string('bid_number')->nullable()->after('state');
            $table->string('ra_emd')->nullable()->after('bid_number');
            $table->index('city');
            $table->index('bid_number');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['city']);
            $table->dropIndex(['bid_number']);
            $table->dropColumn(['city', 'state', 'bid_number', 'ra_emd']);
        });
    }
};
