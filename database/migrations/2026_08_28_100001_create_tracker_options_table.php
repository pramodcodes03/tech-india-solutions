<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dynamic dropdown values for the operational trackers.
 *
 * The proposal asks for "HR can add new sources at any time" on the Visitor
 * tracker (and the same for Purpose of Visit / Break Type). Rather than three
 * near-identical lookup tables, every dropdown lives here keyed by `type`, so
 * one settings screen manages all of them and a new dropdown later costs a
 * string constant instead of a migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracker_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40);          // break_type | visitor_source | visit_purpose
            $table->string('name', 120);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['business_id', 'type', 'name']);
            $table->index(['business_id', 'type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracker_options');
    }
};
