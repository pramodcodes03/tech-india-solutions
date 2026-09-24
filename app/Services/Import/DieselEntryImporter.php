<?php

namespace App\Services\Import;

use App\Models\DieselEntry;

/**
 * Excel/CSV import of historical diesel purchases.
 *
 * A blank Serial No is auto-numbered (DSL-YYYYMM-NNNN) so a legacy sheet that
 * never carried serials still imports cleanly; a supplied serial is kept and
 * checked for duplicates.
 */
class DieselEntryImporter implements RowImporter
{
    use ParsesSpreadsheetValues;

    public function key(): string
    {
        return 'diesel_entries';
    }

    public function label(): string
    {
        return 'Diesel Entries';
    }

    public function permission(): string
    {
        return 'diesel_tracker.import';
    }

    public function templateHeaders(): array
    {
        return ['Serial No', 'Date', 'Time', 'Bill No', 'Slip No', 'Vehicle No', 'Quantity', 'Amount', 'Remarks'];
    }

    public function sampleRow(): array
    {
        return ['', date('Y-m-d'), '10:15', 'B-4471', 'S-119', 'MH12AB1234', '35.50', '3550.00', 'Site vehicle'];
    }

    public function validateRow(array $row, int $businessId): array
    {
        $errors = [];

        if (! $this->parseDate($row['date'] ?? '')) {
            $errors[] = 'Date is missing or not a valid date.';
        }

        $quantity = $this->parseNumber($row['quantity'] ?? '');
        if ($quantity === null || $quantity <= 0) {
            $errors[] = 'Quantity must be a number greater than zero.';
        }

        $amount = $this->parseNumber($row['amount'] ?? '');
        if ($amount === null || $amount <= 0) {
            $errors[] = 'Amount must be a number greater than zero.';
        }

        $serial = trim($row['serial no'] ?? '');
        if ($serial !== '' && DieselEntry::where('serial_no', $serial)->exists()) {
            $errors[] = "Serial No \"{$serial}\" already exists.";
        }

        $time = trim($row['time'] ?? '');
        if ($time !== '' && ! $this->parseTime($time)) {
            $errors[] = 'Time is not a valid time (use HH:MM).';
        }

        return $errors;
    }

    public function importRow(array $row, int $businessId): void
    {
        $date = $this->parseDate($row['date']);
        $quantity = (float) $this->parseNumber($row['quantity']);
        $amount = (float) $this->parseNumber($row['amount']);
        $serial = trim($row['serial no'] ?? '');

        DieselEntry::create([
            'business_id' => $businessId,
            'serial_no' => $serial !== '' ? $serial : DieselEntry::nextSerial($date, $businessId),
            'entry_date' => $date,
            'entry_time' => $this->parseTime($row['time'] ?? ''),
            'bill_no' => trim($row['bill no'] ?? '') ?: null,
            'slip_no' => trim($row['slip no'] ?? '') ?: null,
            'vehicle_no' => trim($row['vehicle no'] ?? '') ?: null,
            'quantity' => $quantity,
            'amount' => $amount,
            'rate_per_litre' => $quantity > 0 ? round($amount / $quantity, 2) : null,
            'remarks' => trim($row['remarks'] ?? '') ?: null,
        ]);
    }
}
