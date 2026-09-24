<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module A — Performance Management, part 1 of 4: the master data.
 *
 *   performance_cycles  the review period everything else hangs off
 *   kras                Key Result Area master (what people are measured on)
 *   kpis                Key Performance Indicators under a KRA (how it is measured)
 *   performance_bands   score → band mapping, HR-editable, with the optional
 *                       bell-curve distribution target per band
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);                       // "Q1 2026-27"
            $table->string('frequency', 20);                   // monthly|quarterly|half_yearly|yearly
            $table->date('period_start');
            $table->date('period_end');
            $table->date('self_review_due')->nullable();
            $table->date('manager_review_due')->nullable();
            $table->date('hr_review_due')->nullable();
            // draft → open (assessments accepted) → locked (scores frozen) → closed
            $table->string('status', 20)->default('draft');
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('locked_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('notes', 500)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index(['business_id', 'status']);
            $table->index(['business_id', 'period_start', 'period_end']);
        });

        Schema::create('kras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30);                        // KRA-0001
            $table->string('name', 150);
            $table->text('description')->nullable();
            // Null on either means "applies to everyone" — the master is a
            // template, narrowed at assignment time.
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('designation_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('weightage', 5, 2)->default(0);    // default share of the 100%
            $table->string('review_frequency', 20)->default('quarterly');
            $table->foreignId('manager_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('status', 20)->default('active');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->unique(['business_id', 'code']);
            $table->index(['business_id', 'status']);
            $table->index(['business_id', 'department_id']);
        });

        Schema::create('kpis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('kra_id')->constrained('kras')->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('measurement_unit', 20)->default('number');  // number|percentage|currency|hours|rating
            $table->decimal('target_value', 14, 2)->default(0);
            $table->decimal('weightage', 5, 2)->default(0);             // share within the parent KRA
            // How achieved is scored against target:
            //   higher_better  sales, output      → achieved/target
            //   lower_better   defects, downtime  → target/achieved
            //   exact_match    attendance targets → full marks only on target
            $table->string('score_formula', 20)->default('higher_better');
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->unique(['business_id', 'code']);
            $table->index(['business_id', 'kra_id', 'status']);
        });

        Schema::create('performance_bands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60);                        // Outstanding, Excellent, …
            $table->decimal('min_score', 5, 2);
            $table->decimal('max_score', 5, 2);
            $table->string('color', 20)->default('#4361ee');
            // Reward the band suggests: increment|bonus|promotion|training|pip|none
            $table->string('recommendation', 20)->default('none');
            // Share of the workforce this band should hold when the bell curve
            // is switched on. Null = not part of the curve.
            $table->decimal('bell_curve_percent', 5, 2)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['business_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_bands');
        Schema::dropIfExists('kpis');
        Schema::dropIfExists('kras');
        Schema::dropIfExists('performance_cycles');
    }
};
