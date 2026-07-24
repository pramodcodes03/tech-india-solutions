<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets each helpdesk category route tickets to ONE specific handler:
 *  - assigned_role: the role chosen on the category form
 *  - assigned_admin_id: the specific admin (of that role) who receives tickets
 * When set, a new ticket in this category goes only to that admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('internal_ticket_categories', function (Blueprint $table) {
            $table->string('assigned_role', 100)->nullable()->after('name');
            $table->foreignId('assigned_admin_id')->nullable()->after('assigned_role')
                ->constrained('admins')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('internal_ticket_categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_admin_id');
            $table->dropColumn('assigned_role');
        });
    }
};
