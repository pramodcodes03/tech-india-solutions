<?php

namespace App\Services\Import;

use Illuminate\Support\Carbon;

/**
 * Date / time / number normalisation shared by the tracker importers.
 *
 * Spreadsheets hand over the same value in a dozen shapes ("9:5", "09:05:00",
 * "1,250.00", "01-08-2026"); these helpers reduce them to what the columns
 * expect, and return null when the cell simply cannot be read as that type.
 */
trait ParsesSpreadsheetValues
{
    protected function parseDate(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        // dd-mm-yyyy and dd/mm/yyyy are what Indian sheets contain; Carbon would
        // otherwise read 01-08-2026 as 8 January.
        if (preg_match('#^(\d{1,2})[-/](\d{1,2})[-/](\d{4})$#', $value, $m)) {
            $value = "{$m[3]}-{$m[2]}-{$m[1]}";
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    /** Normalise "9:5", "09:05" and "09:05:00" to "09:05". */
    protected function parseTime(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '' || ! preg_match('/^(\d{1,2}):(\d{2})(:\d{2})?$/', $value, $m)) {
            return null;
        }

        $hour = (int) $m[1];
        $minute = (int) $m[2];

        return ($hour > 23 || $minute > 59) ? null : sprintf('%02d:%02d', $hour, $minute);
    }

    /** "₹1,250.50" → 1250.50; null when the cell is not a number at all. */
    protected function parseNumber(?string $value): ?float
    {
        $clean = str_replace([',', '₹', ' '], '', trim((string) $value));

        return $clean !== '' && is_numeric($clean) ? (float) $clean : null;
    }
}
