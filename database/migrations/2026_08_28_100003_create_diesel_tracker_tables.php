<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Diesel Tracker — fuel purchases with the bill/slip proof attached, plus the
 * monthly budget they are measured against.
 *
 * Budgets are a separate table keyed on the first day of the month so
 * "allocated vs consumed vs remaining" can be shown for any month without
 * stuffing a budget column onto every entry row.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diesel_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('serial_no', 40);               // DSL-202608-0001, editable
            $table->date('entry_date');
            $table->time('entry_time')->nullable();
            $table->string('bill_no', 60)->nullable();
            $table->string('slip_no', 60)->nullable();
            $table->string('vehicle_no', 40)->nullable();
            $table->decimal('quantity', 10, 2);            // litres
            $table->decimal('amount', 12, 2);
            $table->decimal('rate_per_litre', 10, 2)->nullable();  // amount / quantity, stored for trend charts
            $table->string('attachment')->nullable();      // receipt / slip — PDF or image
            $table->string('remarks', 500)->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->unique(['business_id', 'serial_no']);
            $table->index(['business_id', 'entry_date']);
        });

        Schema::create('diesel_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->date('period_month');                  // always the 1st of the month
            $table->decimal('amount', 14, 2);
            $table->string('notes', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->unique(['business_id', 'period_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diesel_budgets');
        Schema::dropIfExists('diesel_entries');
    }
};
