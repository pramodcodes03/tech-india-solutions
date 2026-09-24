<?php

namespace App\Services\Import;

use App\Models\TrackerOption;
use App\Models\VisitorLog;

/**
 * Excel/CSV import of historical visitor / candidate rows.
 *
 * Source and Purpose are matched by name and created on the fly when new — the
 * same "HR can add a source at any time" behaviour the form has, applied to a
 * bulk file.
 */
class VisitorLogImporter implements RowImporter
{
    use ParsesSpreadsheetValues;

    public function key(): string
    {
        return 'visitor_logs';
    }

    public function label(): string
    {
        return 'Visitor Log Entries';
    }

    public function permission(): string
    {
        return 'visitor_tracker.import';
    }

    public function templateHeaders(): array
    {
        return ['Date Of Visit', 'Visitor Name', 'Mobile', 'Source', 'Arrival Time', 'Purpose', 'Called By', 'Interview By', 'Availability Status', 'Outcome', 'Remarks'];
    }

    public function sampleRow(): array
    {
        return [date('Y-m-d'), 'Ravi Kumar', '9876543210', 'Naukri', '10:30', 'Interview', 'Priya (HR)', 'Amit (Tech Lead)', 'Available', 'Pending', 'Round 1 done'];
    }

    public function validateRow(array $row, int $businessId): array
    {
        $errors = [];

        if (! $this->parseDate($row['date of visit'] ?? '')) {
            $errors[] = 'Date Of Visit is missing or not a valid date.';
        }

        if (trim($row['visitor name'] ?? '') === '') {
            $errors[] = 'Visitor Name is required.';
        }

        $arrival = trim($row['arrival time'] ?? '');
        if ($arrival !== '' && ! $this->parseTime($arrival)) {
            $errors[] = 'Arrival Time is not a valid time (use HH:MM).';
        }

        if ($this->matchKey($row['availability status'] ?? '', VisitorLog::STATUSES, 'available') === null) {
            $errors[] = 'Availability Status must be one of: '.implode(', ', VisitorLog::STATUSES).'.';
        }

        if ($this->matchKey($row['outcome'] ?? '', VisitorLog::OUTCOMES, 'pending') === null) {
            $errors[] = 'Outcome must be one of: '.implode(', ', VisitorLog::OUTCOMES).'.';
        }

        return $errors;
    }

    public function importRow(array $row, int $businessId): void
    {
        VisitorLog::create([
            'business_id' => $businessId,
            'visit_date' => $this->parseDate($row['date of visit']),
            'visitor_name' => trim($row['visitor name']),
            'mobile' => trim($row['mobile'] ?? '') ?: null,
            'source_id' => $this->optionId(TrackerOption::TYPE_SOURCE, trim($row['source'] ?? ''), $businessId),
            'purpose_id' => $this->optionId(TrackerOption::TYPE_PURPOSE, trim($row['purpose'] ?? ''), $businessId),
            'arrival_time' => $this->parseTime($row['arrival time'] ?? ''),
            'called_by' => trim($row['called by'] ?? '') ?: null,
            'interview_by' => trim($row['interview by'] ?? '') ?: null,
            'availability_status' => $this->matchKey($row['availability status'] ?? '', VisitorLog::STATUSES, 'available'),
            'outcome' => $this->matchKey($row['outcome'] ?? '', VisitorLog::OUTCOMES, 'pending'),
            'remarks' => trim($row['remarks'] ?? '') ?: null,
        ]);
    }

    /**
     * Resolve a cell against a key => label map, accepting either side
     * ("no_show" or "No Show"). Blank falls back to $default; anything
     * unrecognised returns null so validateRow can reject the row.
     */
    private function matchKey(string $value, array $map, string $default): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return $default;
        }

        foreach ($map as $key => $label) {
            if (strcasecmp($value, $key) === 0 || strcasecmp($value, $label) === 0) {
                return $key;
            }
        }

        return null;
    }

    private function optionId(string $type, string $name, int $businessId): ?int
    {
        if ($name === '') {
            return null;
        }

        return TrackerOption::firstOrCreate(
            ['business_id' => $businessId, 'type' => $type, 'name' => $name],
            ['sort_order' => 99, 'is_active' => true],
        )->id;
    }
}
