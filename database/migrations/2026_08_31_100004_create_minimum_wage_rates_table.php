<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The notified minimum wage, by skill category, with effect from a date.
 *
 * Form B prints this as a banner above the wage table — "Rate of Minimum Wages
 * and since the date …" — so it has to be stored as a dated revision rather
 * than a single current figure: reprinting an old month must print the rate
 * that was in force then, not today's.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('minimum_wage_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->date('effective_from');
            $table->enum('skill_category', ['highly_skilled', 'skilled', 'semi_skilled', 'unskilled']);
            $table->decimal('basic', 12, 2)->default(0);
            $table->decimal('da', 12, 2)->default(0);
            $table->string('overtime_note', 120)->default('Double the wages');
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->unique(['business_id', 'effective_from', 'skill_category'], 'min_wage_unique');
            $table->index(['business_id', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('minimum_wage_rates');
    }
};
