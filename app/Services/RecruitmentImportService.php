<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\Designation;
use App\Models\RecruitmentBatch;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

/**
 * Bulk candidate import for large campus drives. Columns (header row):
 *   First Name | Last Name | Email | Phone | Source | Experience | Expected CTC | Designation | Batch
 *
 * Only First Name is mandatory. Designation / Batch are resolved by name
 * against the current business; unknown values are left null rather than failing.
 */
class RecruitmentImportService
{
    public function __construct(private RecruitmentService $recruitment)
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
        if (! isset($map['first name']) && ! isset($map['name'])) {
            return ['imported' => 0, 'failed' => 0, 'errors' => [['row' => 0, 'message' => 'Missing required "First Name" column.']]];
        }

        $this->recruitment->ensureStages($businessId);
        $designations = Designation::pluck('id', 'name')->mapWithKeys(fn ($id, $name) => [strtolower(trim($name)) => $id]);
        $batches = RecruitmentBatch::pluck('id', 'name')->mapWithKeys(fn ($id, $name) => [strtolower(trim($name)) => $id]);

        $imported = 0;
        $failed = 0;
        $errors = [];

        foreach (array_slice($rows, 1) as $i => $row) {
            $line = $i + 2;
            $get = fn (string $key) => isset($map[$key]) ? trim((string) ($row[$map[$key]] ?? '')) : '';

            $first = $get('first name') ?: $get('name');
            if ($first === '') {
                continue; // blank line — skip silently
            }

            try {
                $source = strtolower($get('source'));
                $source = array_key_exists($source, Candidate::SOURCES) ? $source : 'other';

                $this->recruitment->create([
                    'business_id' => $businessId,
                    'first_name' => $first,
                    'last_name' => $get('last name') ?: null,
                    'email' => $get('email') ?: null,
                    'phone' => $get('phone') ?: null,
                    'source' => $source,
                    'total_experience' => is_numeric($get('experience')) ? $get('experience') : null,
                    'expected_ctc' => is_numeric(str_replace(',', '', $get('expected ctc'))) ? str_replace(',', '', $get('expected ctc')) : null,
                    'designation_id' => $designations[strtolower($get('designation'))] ?? null,
                    'batch_id' => $batches[strtolower($get('batch'))] ?? null,
                ]);
                $imported++;
            } catch (Throwable $e) {
                $failed++;
                $errors[] = ['row' => $line, 'message' => $e->getMessage()];
            }
        }

        return ['imported' => $imported, 'failed' => $failed, 'errors' => $errors];
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
