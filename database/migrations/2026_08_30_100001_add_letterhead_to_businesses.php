<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module D — the Letterhead Foundation.
 *
 * Every one of the 42 documents is produced on a shared letterhead, so the
 * pieces that make up that letterhead live on the business rather than being
 * repeated in each template:
 *
 *   signature_path      authorised signature image, printed above the
 *                       signatory block on letters and vouchers
 *   seal_path           company seal / stamp, printed beside it
 *   signatory_name      who the signature belongs to
 *   signatory_role      their designation, printed under the name
 *   letterhead_footer   the footer line (registration numbers, tagline)
 *   letterhead_enabled  off = plain layout, for businesses using pre-printed
 *                       letterhead paper
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('signature_path')->nullable()->after('logo');
            $table->string('seal_path')->nullable()->after('signature_path');
            $table->string('signatory_name', 120)->nullable()->after('seal_path');
            $table->string('signatory_role', 120)->nullable()->after('signatory_name');
            $table->string('letterhead_footer', 500)->nullable()->after('signatory_role');
            $table->boolean('letterhead_enabled')->default(true)->after('letterhead_footer');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn([
                'signature_path', 'seal_path', 'signatory_name',
                'signatory_role', 'letterhead_footer', 'letterhead_enabled',
            ]);
        });
    }
};
