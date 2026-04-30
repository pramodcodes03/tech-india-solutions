<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('default_depreciation_method')->default('straight_line');
            $table->unsignedSmallInteger('default_useful_life_years')->default(5);
            $table->decimal('default_salvage_percent', 5, 2)->default(5);
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['business_id', 'code']);

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_categories');
    }
};
