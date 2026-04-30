<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('legal_name')->nullable();

            // Statutory
            $table->string('gst', 30)->nullable();
            $table->string('pan', 20)->nullable();
            $table->string('cin', 30)->nullable();

            // Address
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('pincode', 10)->nullable();
            $table->string('country')->default('India');

            // Contact
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();

            // Branding
            $table->string('logo')->nullable();

            // Currency
            $table->string('currency_code', 5)->default('INR');
            $table->string('currency_symbol', 5)->default('₹');

            // Document prefixes (independent sequence per business)
            $table->string('invoice_prefix', 20)->default('INV-');
            $table->string('quotation_prefix', 20)->default('QUO-');
            $table->string('sales_order_prefix', 20)->default('SO-');
            $table->string('po_prefix', 20)->default('PO-');
            $table->string('grn_prefix', 20)->default('GRN-');
            $table->string('proforma_prefix', 20)->default('PI-');
            $table->string('employee_code_prefix', 20)->default('EMP-');

            $table->text('terms_and_conditions')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};
