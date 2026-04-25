<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_models', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->foreignId('category_id')->constrained('asset_categories')->restrictOnDelete();
            $table->string('manufacturer')->nullable();
            $table->string('model_number')->nullable();
            $table->json('specifications')->nullable();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->string('default_depreciation_method')->default('straight_line');
            $table->unsignedSmallInteger('default_useful_life_years')->default(5);
            $table->decimal('default_salvage_percent', 5, 2)->default(5);
            $table->unsignedSmallInteger('manufacturer_warranty_months')->default(12);
            $table->string('status')->default('active');
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index('status');
            $table->index('category_id');
            $table->index('manufacturer');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_models');
    }
};
