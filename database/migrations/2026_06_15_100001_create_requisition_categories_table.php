<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Per-business lookup table that drives the Purchase Requisition
        // "Category" dropdown. Previously the options lived in a hardcoded
        // Requisition::CATEGORIES constant; moving them into a table lets
        // each business add/rename/disable categories without a developer.
        //
        // Requisition rows still store the slug string in their existing
        // `category` column — no FK migration, so historical requisitions
        // are untouched and a category can be soft-deleted without orphaning
        // old records.
        //
        // is_system marks the defaults seeded by this codebase: admins can
        // deactivate them but not delete them.
        Schema::create('requisition_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('key', 60);
            $table->string('label', 100);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['business_id', 'key']);
            $table->index(['business_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requisition_categories');
    }
};
