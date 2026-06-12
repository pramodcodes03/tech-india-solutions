<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite can't add FK constraints to an existing table via ALTER, so
        // on sqlite (test DB) add plain columns; MySQL/production keeps the FK.
        $sqlite = DB::getDriverName() === 'sqlite';

        Schema::table('employee_documents', function (Blueprint $table) use ($sqlite) {
            $table->enum('verification_status', ['pending', 'verified', 'rejected'])->default('pending')->after('expires_on');
            $verifiedBy = $table->foreignId('verified_by')->nullable()->after('verification_status');
            $table->timestamp('verified_at')->nullable()->after('verified_by');
            $table->string('verification_remarks')->nullable()->after('verified_at');
            // Distinguish employee self-uploads from admin uploads for the
            // "notify admin on new employee upload" requirement.
            $empUploadedBy = $table->foreignId('employee_uploaded_by')->nullable()->after('uploaded_by');

            if (! $sqlite) {
                $verifiedBy->constrained('admins')->nullOnDelete();
                $empUploadedBy->constrained('employees')->nullOnDelete();
            }
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
