<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which businesses an admin may work in, beyond their own.
 *
 * `admins.business_id` says where an admin *belongs* — it stays the home
 * business and is what every record they create is stamped with. This table
 * says which businesses they may additionally *switch to*, which is a different
 * question: an external accountant belongs to one company but audits several.
 *
 * Before this, switching was gated on the Super Admin role alone, so the only
 * way to let an accountant see a second company's books was to make them a
 * Super Admin — handing over the whole system to solve a reporting problem.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_business', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('admins')->cascadeOnDelete();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('granted_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->unique(['admin_id', 'business_id']);
            $table->index('business_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_business');
    }
};
