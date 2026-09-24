<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The establishment identity block that heads every statutory register.
 *
 * A labour register is filed against the *establishment*, not the company: the
 * registration code, the named employer who signs, the nature of work and the
 * place of work all print verbatim at the top of each form.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('establishment_code', 40)->nullable()->after('cin');
            $table->string('lin', 40)->nullable()->after('establishment_code');
            $table->string('employer_name', 150)->nullable()->after('lin');
            $table->string('employer_designation', 100)->nullable()->after('employer_name');
            $table->text('employer_address')->nullable()->after('employer_designation');
            $table->string('nature_of_work', 150)->nullable()->after('employer_address');
            $table->string('place_of_work', 120)->nullable()->after('nature_of_work');
            $table->string('statutory_state', 60)->default('Punjab')->after('place_of_work');
            $table->string('wage_period', 30)->default('Monthly')->after('statutory_state');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn([
                'establishment_code', 'lin', 'employer_name', 'employer_designation',
                'employer_address', 'nature_of_work', 'place_of_work',
                'statutory_state', 'wage_period',
            ]);
        });
    }
};
