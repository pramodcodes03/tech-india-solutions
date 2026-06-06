<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_documents', function (Blueprint $table) {
            $table->enum('verification_status', ['pending', 'verified', 'rejected'])->default('pending')->after('expires_on');
            $table->foreignId('verified_by')->nullable()->after('verification_status')->constrained('admins')->nullOnDelete();
            $table->timestamp('verified_at')->nullable()->after('verified_by');
            $table->string('verification_remarks')->nullable()->after('verified_at');
            // Distinguish employee self-uploads from admin uploads for the
            // "notify admin on new employee upload" requirement.
            $table->foreignId('employee_uploaded_by')->nullable()->after('uploaded_by')->constrained('employees')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employee_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('verified_by');
            $table->dropConstrainedForeignId('employee_uploaded_by');
            $table->dropColumn(['verification_status', 'verified_at', 'verification_remarks']);
        });
    }
};
