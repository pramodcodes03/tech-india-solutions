<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_tickets', function (Blueprint $table) {
            // A ticket can now be EITHER about a product (existing flow) OR a service
            // category (new flow — electrician, plumber, etc.), or both.
            $table->foreignId('category_id')->nullable()->after('product_id')
                ->constrained('service_categories')->nullOnDelete();

            // Optional: who the work is for / site address for non-product tickets.
            $table->string('site_location')->nullable()->after('issue_description');
            $table->string('contact_name')->nullable()->after('site_location');
            $table->string('contact_phone', 20)->nullable()->after('contact_name');
            $table->dateTime('scheduled_at')->nullable()->after('contact_phone');

            $table->index('category_id');
            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::table('service_tickets', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropIndex(['category_id']);
            $table->dropIndex(['scheduled_at']);
            $table->dropColumn(['category_id', 'site_location', 'contact_name', 'contact_phone', 'scheduled_at']);
        });
    }
};
