<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('card_no', 50)->nullable()->after('employee_code');
            $table->unique(['business_id', 'card_no'], 'employees_business_card_no_unique');
            $table->index('card_no');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique('employees_business_card_no_unique');
            $table->dropIndex(['card_no']);
            $table->dropColumn('card_no');
        });
    }
};
