<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('log_code');
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->string('type')->default('corrective');
            $table->date('scheduled_date')->nullable();
            $table->date('performed_date')->nullable();
            $table->string('performed_by')->nullable();
            $table->foreignId('performed_by_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('vendor_name')->nullable();
            $table->decimal('parts_cost', 15, 2)->default(0);
            $table->decimal('labour_cost', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->decimal('downtime_hours', 8, 2)->default(0);
            $table->text('description')->nullable();
            $table->text('parts_used')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->string('status')->default('completed');
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index('asset_id');
            $table->index('type');
            $table->index('status');
            $table->index('performed_date');
            $table->unique(['business_id', 'log_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_maintenance_logs');
    }
};
