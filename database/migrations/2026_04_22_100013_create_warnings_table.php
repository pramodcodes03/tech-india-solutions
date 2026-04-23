<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warnings', function (Blueprint $table) {
            $table->id();
            $table->string('warning_code')->unique();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->unsignedTinyInteger('level'); // 1 = HR, 2 = Manager, 3 = Director (termination)
            $table->string('title');
            $table->text('reason');
            $table->text('action_required')->nullable();
            $table->date('issued_on');
            $table->enum('status', ['active', 'acknowledged', 'withdrawn', 'escalated'])->default('active');
            $table->timestamp('acknowledged_at')->nullable();
            $table->text('employee_response')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'level']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warnings');
    }
};
