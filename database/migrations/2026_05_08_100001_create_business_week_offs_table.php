<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_week_offs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            // 0=Sunday, 1=Monday, ..., 6=Saturday
            $table->unsignedTinyInteger('day_of_week');
            $table->boolean('is_off')->default(true);
            $table->timestamps();

            $table->unique(['business_id', 'day_of_week']);
        });

        Schema::table('holidays', function (Blueprint $table) {
            // Yearly recurring holidays (e.g. Independence Day 15-Aug) — date stores MM-DD as month+day
            $table->boolean('is_yearly')->default(false)->after('type');
            // Dynamic holiday: employee swapped weekend for weekday leave — fully paid
            $table->boolean('is_dynamic')->default(false)->after('is_yearly');
            // For dynamic holidays: which employee this applies to (null = all)
            $table->foreignId('employee_id')->nullable()->after('is_dynamic')->constrained('employees')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('holidays', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropColumn(['is_yearly', 'is_dynamic', 'employee_id']);
        });

        Schema::dropIfExists('business_week_offs');
    }
};
