<?php

namespace App\Services\Import;

use App\Models\BulkImportLog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

/**
 * Standardized bulk-import pipeline reused across modules:
 *   validate → preview → confirm → log.
 *
 * Importers implement RowImporter; this service handles parsing, header
 * mapping, per-row validation, transactional import and audit logging.
 */
class BulkImportService
{
    /** @var array<string, RowImporter> */
    private array $importers;

    public function __construct(
        EmployeeImporter $employees,
        PayrollAdjustmentImporter $payroll,
        LeaveBalanceImporter $leave,
    ) {
        $this->importers = [
            $employees->key() => $employees,
            $payroll->key() => $payroll,
            $leave->key() => $leave,
        ];
    }

    /** @return array<string, RowImporter> */
    public function all(): array
    {
        return $this->importers;
    }

    public function importer(string $key): ?RowImporter
    {
        return $this->importers[$key] ?? null;
    }

    /**
     * Parse a file into associative rows keyed by lowercased header.
     *
     * @return array{rows: array<int, array<string,string>>, error: ?string}
     */
    public function parse(UploadedFile $file): array
    {
        try {
            $sheets = Excel::toArray(new \stdClass, $file);
        } catch (Throwable $e) {
            return ['rows' => [], 'error' => 'Could not read file: '.$e->getMessage()];
        }

        $raw = $sheets[0] ?? [];
        if (count($raw) < 2) {
            return ['rows' => [], 'error' => 'File has no data rows.'];
        }

        $headers = array_map(fn ($h) => strtolower(trim((string) $h)), $raw[0]);
        $rows = [];
        foreach (array_slice($raw, 1) as $line) {
            $assoc = [];
            foreach ($headers as $i => $h) {
                $assoc[$h] = isset($line[$i]) ? (string) $line[$i] : '';
            }
            // Skip wholly empty rows.
            if (implode('', array_map('trim', $assoc)) === '') {
                continue;
            }
            $rows[] = $assoc;
        }

        return ['rows' => $rows, 'error' => null];
    }

    /**
     * Validate every row; returns valid rows + a per-row error list.
     *
     * @return array{valid: array<int,array>, errors: array<int,array{row:int,messages:array}>}
     */
    public function validate(array $rows, RowImporter $importer, int $businessId): array
    {
        $valid = [];
        $errors = [];
        foreach ($rows as $i => $row) {
            $msgs = $importer->validateRow($row, $businessId);
            if ($msgs) {
                $errors[] = ['row' => $i + 2, 'messages' => $msgs];
            } else {
                $valid[] = $row;
            }
        }

        return ['valid' => $valid, 'errors' => $errors];
    }

    /**
     * Import already-validated rows in a transaction and write an audit log.
     *
     * @return array{imported:int, failed:int, errors: array}
     */
    public function import(array $rows, RowImporter $importer, int $businessId, ?string $fileName = null): array
    {
        $imported = 0;
        $failed = 0;
        $errors = [];

        foreach ($rows as $i => $row) {
            try {
                DB::transaction(fn () => $importer->importRow($row, $businessId));
                $imported++;
            } catch (Throwable $e) {
                $failed++;
                $errors[] = ['row' => $i + 2, 'messages' => [$e->getMessage()]];
            }
        }

        BulkImportLog::create([
            'business_id' => $businessId,
            'type' => $importer->key(),
            'file_name' => $fileName,
            'total_rows' => count($rows),
            'imported' => $imported,
            'failed' => $failed,
            'errors' => $errors,
            'admin_id' => Auth::guard('admin')->id(),
        ]);

        return ['imported' => $imported, 'failed' => $failed, 'errors' => $errors];
    }
}
