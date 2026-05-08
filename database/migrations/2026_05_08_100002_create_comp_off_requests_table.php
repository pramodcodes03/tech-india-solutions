<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comp_off_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            // The week-off day the employee actually worked
            $table->date('worked_on');
            // The working day the employee wants off in exchange
            $table->date('comp_date');
            $table->string('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('actioned_at')->nullable();
            $table->string('admin_remarks')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'status']);
            $table->index('comp_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comp_off_requests');
    }
};
