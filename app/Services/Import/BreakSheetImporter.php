<?php

namespace App\Services\Import;

use App\Models\BreakSheet;
use App\Models\Employee;
use App\Models\TrackerOption;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Excel/CSV import of historical break-sheet rows.
 *
 * Break Type is matched by name and created on the fly if it is new, so a
 * year of legacy sheets imports without HR pre-registering every label first.
 */
class BreakSheetImporter implements RowImporter
{
    use ParsesSpreadsheetValues;

    public function key(): string
    {
        return 'break_sheets';
    }

    public function label(): string
    {
        return 'Break Sheet Entries';
    }

    public function permission(): string
    {
        return 'break_tracker.import';
    }

    public function templateHeaders(): array
    {
        return ['Employee Code', 'Break Date', 'Out Time', 'In Time', 'Break Type', 'Remarks'];
    }

    public function sampleRow(): array
    {
        return ['EMP-0001', date('Y-m-d'), '13:30', '14:05', 'Lunch Break', 'Canteen'];
    }

    public function validateRow(array $row, int $businessId): array
    {
        $errors = [];

        $code = trim($row['employee code'] ?? '');
        if ($code === '') {
            $errors[] = 'Employee Code is required.';
        } elseif (! Employee::where('employee_code', $code)->exists()) {
            $errors[] = "Unknown employee code \"{$code}\".";
        }

        if (! $this->parseDate($row['break date'] ?? '')) {
            $errors[] = 'Break Date is missing or not a valid date.';
        }

        if (! $this->parseTime($row['out time'] ?? '')) {
            $errors[] = 'Out Time is missing or not a valid time (use HH:MM).';
        }

        $in = trim($row['in time'] ?? '');
        if ($in !== '' && ! $this->parseTime($in)) {
            $errors[] = 'In Time is not a valid time (use HH:MM).';
        }

        return $errors;
    }

    public function importRow(array $row, int $businessId): void
    {
        $employee = Employee::where('employee_code', trim($row['employee code']))->firstOrFail();

        $out = $this->parseTime($row['out time']);
        $in = $this->parseTime(trim($row['in time'] ?? '')) ?: null;

        // Keyed on the natural identity of a break rather than created blind:
        // one employee cannot start two breaks at the same minute on the same
        // date, so re-importing a sheet (a retry, a double submit, or the same
        // file sent twice) corrects the existing rows instead of doubling
        // every duration — which silently doubles the day's combined total.
        $key = [
            'business_id' => $businessId,
            'employee_id' => $employee->id,
            'break_date' => $this->parseDate($row['break date']),
            'out_time' => $out,
        ];

        $values = [
            'in_time' => $in,
            'duration_minutes' => BreakSheet::minutesBetween($out, $in),
            'break_type_id' => $this->breakTypeId(trim($row['break type'] ?? ''), $businessId),
            'remarks' => trim($row['remarks'] ?? '') ?: null,
        ];

        try {
            BreakSheet::updateOrCreate($key, $values);
        } catch (UniqueConstraintViolationException) {
            // updateOrCreate is a SELECT then an INSERT. Two imports running at
            // once (an impatient second click on Confirm) can both miss on the
            // SELECT; the unique key then rejects the loser's INSERT rather
            // than letting a duplicate through. The row the winner wrote is the
            // one this call meant to write, so update it and carry on — the
            // sheet still imports cleanly instead of reporting a failed row.
            BreakSheet::where($key)->firstOrFail()->update($values);
        }
    }

    private function breakTypeId(string $name, int $businessId): ?int
    {
        if ($name === '') {
            return null;
        }

        return TrackerOption::firstOrCreate(
            ['business_id' => $businessId, 'type' => TrackerOption::TYPE_BREAK, 'name' => $name],
            ['sort_order' => 99, 'is_active' => true],
        )->id;
    }
}
