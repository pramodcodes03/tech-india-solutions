<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campus recruitment batches — group candidates that came in from a single
 * campus drive so they can be tracked and reported together.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('institution', 160)->nullable();
            $table->date('drive_date')->nullable();
            $table->string('coordinator', 120)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->index(['business_id', 'drive_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_batches');
    }
};
