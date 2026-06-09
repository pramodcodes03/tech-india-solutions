<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Department / employee-category salary templates. Applying a template to many
 * employees creates a salary structure for each from the template's component
 * amounts — so HR no longer maintains structures one employee at a time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            // Scope: department-level or employee-category level.
            $table->enum('level', ['department', 'category', 'generic'])->default('generic');
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('employee_category', 60)->nullable(); // e.g. employment_type value

            // Monthly component amounts (mirrors salary_structures).
            $table->decimal('basic', 12, 2)->default(0);
            $table->decimal('hra', 12, 2)->default(0);
            $table->decimal('conveyance', 12, 2)->default(0);
            $table->decimal('medical', 12, 2)->default(0);
            $table->decimal('special', 12, 2)->default(0);
            $table->decimal('other_allowance', 12, 2)->default(0);

            $table->decimal('pf_percent', 5, 2)->default(12);
            $table->decimal('esi_percent', 5, 2)->default(0.75);
            $table->decimal('professional_tax', 8, 2)->default(0);

            $table->boolean('status')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index(['business_id', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_templates');
    }
};
