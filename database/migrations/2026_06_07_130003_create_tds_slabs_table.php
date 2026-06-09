<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Income-tax (TDS) slabs per financial year. The TDS engine annualises taxable
 * income, applies these slabs progressively, and divides by 12 for the monthly
 * TDS — replacing the previous single fixed monthly amount.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tds_slabs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('financial_year', 9); // e.g. 2026-2027
            $table->decimal('lower', 14, 2)->default(0);
            $table->decimal('upper', 14, 2)->nullable(); // null = no upper bound
            $table->decimal('rate_percent', 5, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['business_id', 'financial_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tds_slabs');
    }
};
