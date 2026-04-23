<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating'); // 1-5
            $table->text('feedback');
            $table->boolean('is_anonymous')->default(false);
            $table->timestamps();

            $table->index(['department_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_feedback');
    }
};
