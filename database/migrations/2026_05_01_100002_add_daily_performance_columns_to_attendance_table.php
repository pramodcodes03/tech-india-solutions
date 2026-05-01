<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $table->string('shift', 50)->nullable()->after('status');
            $table->time('start_time')->nullable()->after('shift');
            $table->string('late_hours', 10)->nullable()->after('check_in');
            $table->string('early_hours', 10)->nullable()->after('check_out');
            $table->string('over_time', 10)->nullable()->after('early_hours');
            $table->decimal('in_temp', 5, 2)->nullable()->after('over_time');
            $table->decimal('out_temp', 5, 2)->nullable()->after('in_temp');
            $table->string('card_no', 50)->nullable()->after('out_temp');
        });
    }

    public function down(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $table->dropColumn([
                'shift',
                'start_time',
                'late_hours',
                'early_hours',
                'over_time',
                'in_temp',
                'out_temp',
                'card_no',
            ]);
        });
    }
};
