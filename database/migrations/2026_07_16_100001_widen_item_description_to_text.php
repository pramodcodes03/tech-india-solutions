<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Line-item `description` was VARCHAR(255), which truncated / rejected longer
 * product descriptions (e.g. a full marketing paragraph pulled from the product
 * catalogue) and crashed the save with "Data too long for column 'description'".
 * Widen it to TEXT on every document's item table so any length is accepted.
 */
return new class extends Migration
{
    private array $tables = [
        'quotation_items',
        'invoice_items',
        'proforma_invoice_items',
        'sales_order_items',
        'purchase_order_items',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'description')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->text('description')->nullable(false)->change();
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'description')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->string('description', 255)->nullable(false)->change();
                });
            }
        }
    }
};
