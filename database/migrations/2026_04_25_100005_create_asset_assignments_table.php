<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('assignment_code')->unique();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->foreignId('from_location_id')->nullable()->constrained('asset_locations')->nullOnDelete();
            $table->foreignId('to_location_id')->nullable()->constrained('asset_locations')->nullOnDelete();
            $table->date('assigned_at');
            $table->date('returned_at')->nullable();
            $table->string('action_type')->default('assign');
            $table->string('condition_at_assign')->nullable();
            $table->string('condition_at_return')->nullable();
            $table->text('notes')->nullable();
            $table->text('return_notes')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index('asset_id');
            $table->index('employee_id');
            $table->index('action_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_assignments');
    }
};
