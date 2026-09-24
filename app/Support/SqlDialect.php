<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Portable SQL fragments for the handful of places we group by month.
 *
 * Production runs MySQL, the test suite runs SQLite; DATE_FORMAT exists only on
 * the former, so month bucketing goes through here instead of being written
 * inline and silently breaking under test.
 */
class SqlDialect
{
    /** Expression yielding "2026-08" for a date column. */
    public static function monthKey(string $column): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', {$column})",
            'pgsql' => "to_char({$column}, 'YYYY-MM')",
            default => "DATE_FORMAT({$column}, '%Y-%m')",
        };
    }

    /** Expression yielding "2026-08-01" — the first day of a date's month. */
    public static function monthStart(string $column): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m-01', {$column})",
            'pgsql' => "to_char(date_trunc('month', {$column}), 'YYYY-MM-DD')",
            default => "DATE_FORMAT({$column}, '%Y-%m-01')",
        };
    }
}
