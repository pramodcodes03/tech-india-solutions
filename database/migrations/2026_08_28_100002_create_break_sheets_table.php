<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Break Sheet Tracker — one row per break taken by an employee.
 *
 * `duration_minutes` is denormalised on save rather than computed in SQL so the
 * analytics view (averages, longest breaks, daily totals) can aggregate without
 * re-deriving time maths per row, and so an imported historical row keeps the
 * duration it was recorded with.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('break_sheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('break_date');
            $table->time('out_time');
            $table->time('in_time')->nullable();          // null = still on break
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->foreignId('break_type_id')->nullable()->constrained('tracker_options')->nullOnDelete();
            $table->string('remarks', 500)->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index(['business_id', 'break_date']);
            $table->index(['business_id', 'employee_id', 'break_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('break_sheets');
    }
};
