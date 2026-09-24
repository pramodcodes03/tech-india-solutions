<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The warning ladder gains two rungs: PIP (mildest, new level 1) and
 * ZTP — Zero Tolerance Policy (most severe, new level 5). The original
 * 1/2/3 (HR / Manager / Director) shift to 2/3/4 so existing warnings
 * keep their meaning. Labels live in Warning::LEVELS.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('warnings')->update(['level' => DB::raw('level + 1')]);
    }

    public function down(): void
    {
        // Shift the original three rungs back; rows created on the new
        // PIP (1) / ZTP (5) rungs have no pre-2026-08 equivalent and are
        // clamped into the old 1..3 range.
        DB::table('warnings')->where('level', 1)->update(['level' => 0]);
        DB::table('warnings')->whereBetween('level', [2, 4])->update(['level' => DB::raw('level - 1')]);
        DB::table('warnings')->where('level', 0)->update(['level' => 1]);
        DB::table('warnings')->where('level', 5)->update(['level' => 3]);
    }
};
