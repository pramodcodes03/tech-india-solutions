<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // CL, SL, PL, ML, COMP
            $table->string('name');
            $table->decimal('annual_quota', 5, 1)->default(0); // days per year
            $table->boolean('is_paid')->default(true);
            $table->boolean('carry_forward')->default(false);
            $table->decimal('max_carry_forward', 5, 1)->default(0);
            $table->boolean('encashable')->default(false);
            $table->string('color', 20)->default('#3b82f6');
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_types');
    }
};
