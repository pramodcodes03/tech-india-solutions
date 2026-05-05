<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();

            // event_key matches NotificationCatalog::events() keys, e.g. "invoice.created"
            $table->string('event_key', 80);

            $table->boolean('is_enabled')->default(true);

            // Optional extra emails to CC for this event (comma-separated → array)
            $table->json('extra_recipients')->nullable();

            // Optional override of recipient roles (e.g. only "manager", not "all_admins")
            // Reserved for future use; default null = use catalog defaults.
            $table->json('recipient_overrides')->nullable();

            $table->foreignId('updated_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->unique(['business_id', 'event_key']);
            $table->index(['business_id', 'is_enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};
