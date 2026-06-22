<?php

namespace App\Services;

use App\Models\Lead;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

/**
 * Bulk lead import. Columns (header row, case-insensitive):
 *   Name | Company | Email | Phone | Source | Expected Value | Lead Received Date | Notes
 *
 * Only Name is mandatory. Source is matched against Lead::SOURCES (by key or
 * label); unknown values fall back to 'other'. Lead Received Date is parsed
 * loosely and defaults to today when missing/invalid.
 */
class LeadImportService
{
    public function __construct(private LeadService $leads)
    {
    }

    /**
     * @return array{imported:int, failed:int, errors: array<int, array{row:int, message:string}>}
     */
    public function import(UploadedFile $file, int $businessId): array
    {
        try {
            $sheets = Excel::toArray(new \stdClass, $file);
        } catch (Throwable $e) {
            return ['imported' => 0, 'failed' => 0, 'errors' => [['row' => 0, 'message' => 'Could not read file: '.$e->getMessage()]]];
        }

        $rows = $sheets[0] ?? [];
        if (count($rows) < 2) {
            return ['imported' => 0, 'failed' => 0, 'errors' => [['row' => 0, 'message' => 'File has no data rows.']]];
        }

        $map = $this->headerMap($rows[0]);
        if (! isset($map['name'])) {
            return ['imported' => 0, 'failed' => 0, 'errors' => [['row' => 0, 'message' => 'Missing required "Name" column.']]];
        }

        // Source lookup: accept either the key (meta_ads) or the label (Meta Ads).
        $sourceByLabel = [];
        foreach (Lead::SOURCES as $key => $label) {
            $sourceByLabel[strtolower($label)] = $key;
        }

        $imported = 0;
        $failed = 0;
        $errors = [];

        foreach (array_slice($rows, 1) as $i => $row) {
            $line = $i + 2;
            $get = fn (string $key) => isset($map[$key]) ? trim((string) ($row[$map[$key]] ?? '')) : '';

            $name = $get('name');
            if ($name === '') {
                continue; // blank line — skip silently
            }

            try {
                $rawSource = strtolower($get('source'));
                $source = array_key_exists($rawSource, Lead::SOURCES)
                    ? $rawSource
                    : ($sourceByLabel[$rawSource] ?? 'other');

                $value = str_replace([',', '₹'], '', $get('expected value'));

                $this->leads->create([
                    'business_id'    => $businessId,
                    'name'           => $name,
                    'company'        => $get('company') ?: null,
                    'email'          => $get('email') ?: null,
                    'phone'          => $get('phone') ?: null,
                    'source'         => $source,
                    'expected_value' => is_numeric($value) ? $value : null,
                    'lead_date'      => $this->parseDate($get('lead received date')),
                    'notes'          => $get('notes') ?: null,
                    'status'         => 'new',
                ]);
                $imported++;
            } catch (Throwable $e) {
                $failed++;
                $errors[] = ['row' => $line, 'message' => $e->getMessage()];
            }
        }

        return ['imported' => $imported, 'failed' => $failed, 'errors' => $errors];
    }

    private function parseDate(string $value): ?string
    {
        if ($value === '') {
            return null; // LeadService defaults to today.
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (Throwable $e) {
            return null;
        }
    }

    private function headerMap(array $header): array
    {
        $map = [];
        foreach ($header as $idx => $col) {
            $map[strtolower(trim((string) $col))] = $idx;
        }

        return $map;
    }
}
