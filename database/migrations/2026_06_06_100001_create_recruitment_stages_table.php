<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Configurable hiring pipeline stages. Each business defines its own ordered
 * list (Applied → Screened → Interview → Offer → Hired / Rejected by default,
 * but fully admin-editable). `type` distinguishes the two terminal buckets so
 * reports can compute conversion / rejection without hard-coding stage names.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('slug', 80);
            // open  = an in-pipeline stage; hired / rejected = terminal buckets.
            $table->enum('type', ['open', 'hired', 'rejected'])->default('open');
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('color', 20)->default('#6366f1');
            $table->boolean('is_default')->default(false); // landing stage for new candidates
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->unique(['business_id', 'slug']);
            $table->index(['business_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_stages');
    }
};
