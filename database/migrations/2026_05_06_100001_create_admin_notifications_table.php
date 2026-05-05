<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-admin inbox for in-app notifications. The notification dispatcher
 * already creates rows in `notification_logs` (the email send-log) — this
 * table is a parallel record scoped to a specific admin user, used to
 * power the bell icon and inbox page.
 *
 * One notification_logs row → many admin_notifications rows (one per
 * matched recipient admin). They share a logical event but live in
 * separate tables because the email log is best-effort delivery audit
 * while this is a per-user unread tracker.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('admin_id')->constrained('admins')->cascadeOnDelete();

            $table->string('event_key', 80);
            $table->string('title');
            $table->string('body', 500)->nullable();
            $table->string('link', 500)->nullable();   // deep-link URL for the bell-click action

            // What this notification is about (polymorphic). Useful for
            // collapsing duplicates and rendering type-specific icons.
            $table->string('related_type')->nullable();
            $table->unsignedBigInteger('related_id')->nullable();

            $table->timestamp('read_at')->nullable();

            $table->timestamps();

            // Hot path: "unread for this admin, newest first".
            $table->index(['admin_id', 'read_at', 'created_at']);
            $table->index(['business_id', 'event_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notifications');
    }
};
