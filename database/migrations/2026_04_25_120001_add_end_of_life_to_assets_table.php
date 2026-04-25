<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->date('end_of_life_date')->nullable()->after('insurance_expiry_date');
            $table->index('end_of_life_date');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropIndex(['end_of_life_date']);
            $table->dropColumn('end_of_life_date');
        });
    }
};
