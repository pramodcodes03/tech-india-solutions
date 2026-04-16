<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('status')->default('active')->after('password');
            $table->dateTime('last_login_at')->nullable()->after('status');
            $table->softDeletes();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropSoftDeletes();
            $table->dropColumn(['phone', 'status', 'last_login_at']);
        });
    }
};
