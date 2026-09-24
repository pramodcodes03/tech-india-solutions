<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fields the statutory labour registers need but the HR record never had.
 *
 * Every Punjab register — Form C, D, E, I, II, II-A, IV — prints a
 * "Father's / Husband's name" column, and Form B prices a row off the
 * employee's skill category against the notified minimum wage.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('father_name', 120)->nullable()->after('last_name');
            $table->string('husband_name', 120)->nullable()->after('father_name');
            $table->enum('skill_category', ['highly_skilled', 'skilled', 'semi_skilled', 'unskilled'])
                ->nullable()->after('employment_type');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['father_name', 'husband_name', 'skill_category']);
        });
    }
};
