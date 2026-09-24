<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The six bands and the bell-curve distribution from the proposal, seeded per
 * business so the scoring engine has something to map against on day one.
 * Every value is editable by HR from Performance → Bands & Bell Curve.
 */
return new class extends Migration
{
    private array $bands = [
        // name, min, max, colour, recommendation, bell-curve share
        ['Outstanding',        95, 100, '#00ab55', 'promotion', 10],
        ['Excellent',          85, 94.99, '#4361ee', 'increment', 20],
        ['Very Good',          75, 84.99, '#2196f3', 'bonus',     null],
        ['Good',               60, 74.99, '#e2a03f', 'none',      50],
        ['Needs Improvement',  40, 59.99, '#f97316', 'training',  15],
        ['Unsatisfactory',      0, 39.99, '#e7515a', 'pip',        5],
    ];

    public function up(): void
    {
        $now = now();

        foreach (DB::table('businesses')->pluck('id') as $businessId) {
            // Skip a business that already has bands, so a re-run is harmless.
            if (DB::table('performance_bands')->where('business_id', $businessId)->exists()) {
                continue;
            }

            $rows = [];
            foreach ($this->bands as $i => [$name, $min, $max, $color, $recommendation, $curve]) {
                $rows[] = [
                    'business_id' => $businessId,
                    'name' => $name,
                    'min_score' => $min,
                    'max_score' => $max,
                    'color' => $color,
                    'recommendation' => $recommendation,
                    'bell_curve_percent' => $curve,
                    'sort_order' => $i + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('performance_bands')->insert($rows);
        }
    }

    public function down(): void
    {
        DB::table('performance_bands')->whereIn('name', array_column($this->bands, 0))->delete();
    }
};
