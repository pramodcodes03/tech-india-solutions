<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_code')->unique();
            $table->string('name');
            $table->string('serial_number')->nullable();
            $table->foreignId('category_id')->constrained('asset_categories')->restrictOnDelete();
            $table->foreignId('asset_model_id')->nullable()->constrained('asset_models')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('asset_locations')->nullOnDelete();
            $table->foreignId('current_custodian_id')->nullable()->constrained('employees')->nullOnDelete();

            // Acquisition
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_cost', 15, 2)->default(0);
            $table->decimal('salvage_value', 15, 2)->default(0);
            $table->date('warranty_expiry_date')->nullable();
            $table->date('insurance_expiry_date')->nullable();

            // Depreciation
            $table->string('depreciation_method')->default('straight_line');
            $table->unsignedSmallInteger('useful_life_years')->default(5);
            $table->date('depreciation_start_date')->nullable();
            $table->date('last_depreciation_posted_on')->nullable();
            $table->decimal('accumulated_depreciation', 15, 2)->default(0);
            $table->decimal('current_book_value', 15, 2)->default(0);

            // State
            $table->string('status')->default('in_storage');
            $table->string('condition_rating')->default('good');
            $table->boolean('is_lost')->default(false);

            // Tagging
            $table->string('qr_code_path')->nullable();
            $table->string('image_path')->nullable();

            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index('status');
            $table->index('category_id');
            $table->index('location_id');
            $table->index('current_custodian_id');
            $table->index('warranty_expiry_date');
            $table->index('asset_model_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
