<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only audit log of every verification action on an employee document
 * — who verified/rejected it, when, and with what remarks.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_document_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_document_id')->constrained()->cascadeOnDelete();
            $table->enum('action', ['uploaded', 'verified', 'rejected', 're_uploaded']);
            $table->string('remarks')->nullable();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamps();

            $table->index('employee_document_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_document_verifications');
    }
};
