<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penalty_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g. "ID Card not worn", "Mobile Phone on floor"
            $table->text('description')->nullable();
            $table->decimal('default_amount', 10, 2)->default(0); // admin can override per-penalty
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penalty_types');
    }
};
