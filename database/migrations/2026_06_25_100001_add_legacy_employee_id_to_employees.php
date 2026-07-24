<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A second, optional employee identifier — the organisation's old/legacy
 * employee ID carried over from a previous system. Nullable + unique (MySQL
 * allows multiple NULLs in a unique index, so it's optional but can't clash).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('legacy_employee_id', 50)->nullable()->unique()->after('employee_code');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique(['legacy_employee_id']);
            $table->dropColumn('legacy_employee_id');
        });
    }
};
