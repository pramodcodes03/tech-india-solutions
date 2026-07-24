<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bulk_import_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40); // employees, payroll_adjustments, leave_balances
            $table->string('file_name')->nullable();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('imported')->default(0);
            $table->unsignedInteger('failed')->default(0);
            $table->json('errors')->nullable();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index(['business_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_import_logs');
    }
};
