<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which helpdesk departments an admin may report on.
 *
 * Internal tickets carry a fixed department (hr / it / admin / accounts), and
 * the client wants Helpdesk Report access cut along that line: whoever runs HR
 * should see HR tickets and nothing else.
 *
 * An admin with NO rows here is unrestricted — that keeps every existing admin
 * working exactly as before, and makes the restriction something you opt a user
 * into rather than something that silently locks everyone out on deploy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_ticket_departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('admins')->cascadeOnDelete();
            $table->enum('department', ['hr', 'it', 'admin', 'accounts']);
            $table->foreignId('granted_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->unique(['admin_id', 'department']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_ticket_departments');
    }
};
