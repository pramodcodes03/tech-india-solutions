<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Per-business lookup tables that drive the Asset "Status" and
        // Maintenance-Log "Type" dropdowns. Storing them in tables instead
        // of code lets each business add their own options (e.g. "Sold Out
        // To Advisors") without a developer changing an enum constant.
        //
        // The asset / maintenance_log rows still store the slug string in
        // their existing `status` / `type` column — no FK migration on
        // those tables, no risk to history. Re-using the string slug means
        // an option can be soft-deleted without orphaning historical rows.
        //
        // is_system marks the defaults seeded by this codebase — admins
        // can deactivate (toggle is_active) but not delete those, so the
        // built-in workflows that look up specific slugs (e.g. 'disposed')
        // still work.
        foreach (['asset_statuses', 'asset_maintenance_types'] as $tbl) {
            Schema::create($tbl, function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->string('key', 60);
                $table->string('label', 100);
                $table->string('color', 30)->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->boolean('is_system')->default(false);
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['business_id', 'key']);
                $table->index(['business_id', 'is_active']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_maintenance_types');
        Schema::dropIfExists('asset_statuses');
    }
};
