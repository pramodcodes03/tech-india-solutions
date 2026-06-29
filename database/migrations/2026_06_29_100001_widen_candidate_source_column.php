<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * candidates.source was a restrictive ENUM with only the original 6 values, so
 * the newly-added recruitment sources (employment_exchange, satyam_skill_center,
 * etc.) failed with "Data truncated". Convert it to a plain VARCHAR — the
 * allowed values are now enforced in validation against Candidate::SOURCES.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `candidates` MODIFY `source` VARCHAR(50) NOT NULL DEFAULT 'other'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `candidates` MODIFY `source` ENUM('walkin','referral','campus','online','agency','other') NOT NULL DEFAULT 'other'");
    }
};
