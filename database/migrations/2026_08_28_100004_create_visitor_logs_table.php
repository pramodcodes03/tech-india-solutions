<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Daily Visitor Tracker — office visitors and interview candidates.
 *
 * `outcome` is what makes the "interview conversion" analytic possible: the
 * availability status records whether the person turned up, the outcome records
 * what came of it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitor_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->date('visit_date');
            $table->string('visitor_name', 150);
            $table->string('mobile', 20)->nullable();
            $table->foreignId('source_id')->nullable()->constrained('tracker_options')->nullOnDelete();
            $table->foreignId('purpose_id')->nullable()->constrained('tracker_options')->nullOnDelete();
            $table->time('arrival_time')->nullable();
            $table->string('called_by', 120)->nullable();
            $table->string('interview_by', 120)->nullable();
            $table->string('availability_status', 20)->default('available'); // available|not_available|rescheduled|no_show
            $table->string('outcome', 20)->default('pending');               // pending|selected|rejected|on_hold|joined
            $table->string('remarks', 500)->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index(['business_id', 'visit_date']);
            $table->index(['business_id', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitor_logs');
    }
};
