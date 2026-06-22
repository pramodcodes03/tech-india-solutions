<?php

namespace App\Services\Asset;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetLocation;
use App\Models\AssetModel;
use App\Models\Employee;
use App\Models\PurchaseOrder;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

/**
 * Bulk-import assets from a CSV/XLS/XLSX file whose headers match the
 * AssetRegisterExport output. Each row is validated and inserted in its
 * own try/catch so one bad row never aborts the whole import.
 *
 * Mandatory columns: Asset Code, Name, Serial Number, Category, Manufacturer, Location.
 * Other columns may be left blank.
 *
 * Related entities (Category, Location, Vendor, Custodian, PO) are
 * resolved by *name* against the current business's existing records.
 * If not found, the row is skipped with an explanatory error so the user
 * can fix the spreadsheet or pre-create the master records.
 *
 * AssetModel is auto-created if (model_name, manufacturer) combo is new —
 * because manufacturers/models proliferate naturally and the user
 * explicitly listed Manufacturer as mandatory.
 */
class AssetImportService
{
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

        $headerMap = $this->buildHeaderMap($rows[0]);

        // Only Name + Category columns must exist in the file at all.
        // Anything else may be omitted entirely or left blank per row.
        $required = ['name', 'category'];
        $missingCols = array_diff($required, array_keys($headerMap));
        if ($missingCols) {
            return ['imported' => 0, 'failed' => 0, 'errors' => [[
                'row' => 1,
                'message' => 'File is missing required columns: '.implode(', ', $missingCols),
            ]]];
        }

        $imported = 0;
        $updated = 0;
        $failed = 0;
        $errors = [];
        $seenCodes = [];

        DB::beginTransaction();
        try {
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                if (empty(array_filter($row, fn ($v) => $v !== null && $v !== ''))) {
                    continue; // skip blank rows
                }

                $rowNum = $i + 1; // human-readable (header is row 1)

                try {
                    // importRow upserts and reports whether it created or updated.
                    $wasUpdated = $this->importRow($row, $headerMap, $businessId, $seenCodes);
                    $wasUpdated ? $updated++ : $imported++;
                } catch (Throwable $e) {
                    $failed++;
                    $errors[] = ['row' => $rowNum, 'message' => $e->getMessage()];
                }
            }
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            $errors[] = ['row' => 0, 'message' => 'Import aborted: '.$e->getMessage()];
            return ['imported' => 0, 'updated' => 0, 'failed' => $failed, 'errors' => $errors];
        }

        return ['imported' => $imported, 'updated' => $updated, 'failed' => $failed, 'errors' => $errors];
    }

    /**
     * Build a normalised-key → column-index map for the header row.
     * "Asset Code" → 'asset_code', "Useful Life (yrs)" → 'useful_life_yrs'.
     */
    protected function buildHeaderMap(array $header): array
    {
        $map = [];
        foreach ($header as $i => $h) {
            $key = $this->normalizeHeader((string) $h);
            if ($key !== '') {
                $map[$key] = $i;
            }
        }
        return $map;
    }

    protected function normalizeHeader(string $h): string
    {
        return trim(strtolower(preg_replace('/[^a-z0-9]+/i', '_', $h)), '_');
    }

    /**
     * Upsert one asset row, keyed on Asset Code. Creates a new asset when the
     * code is new, otherwise updates the existing asset from the row. Throws
     * on any validation / lookup failure; the caller catches and converts it
     * into a row error.
     *
     * @return bool true when an existing asset was updated, false when created.
     */
    protected function importRow(array $row, array $map, int $businessId, array &$seenCodes): bool
    {
        $get = function (string $key) use ($row, $map) {
            if (! isset($map[$key])) {
                return '';
            }
            $val = $row[$map[$key]] ?? '';
            return trim((string) $val);
        };

        $assetCode    = $get('asset_code');
        $name         = $get('name');
        $serial       = $get('serial_number');
        $categoryName = $get('category');
        $modelName    = $get('model');
        $manufacturer = $get('manufacturer');
        $locationName = $get('location');

        // ── Mandatory checks ───────────────────────────────────────────
        // Only Name + Category are required (matches the DB NOT NULL constraints).
        // Everything else is optional; missing values are auto-resolved or stored as null.
        $missing = [];
        if ($name === '')         $missing[] = 'Name';
        if ($categoryName === '') $missing[] = 'Category';

        if (! empty($missing)) {
            throw new \RuntimeException('Missing required field(s): '.implode(', ', $missing));
        }

        // ── Asset Code — auto-generate if blank ────────────────────────
        if ($assetCode === '') {
            $assetCode = $this->generateAssetCode($businessId, $categoryName);
        }

        if (in_array(strtolower($assetCode), $seenCodes, true)) {
            throw new \RuntimeException("Asset Code '{$assetCode}' appears more than once in this file.");
        }
        $seenCodes[] = strtolower($assetCode);

        // Find an existing asset with this code — INCLUDING soft-deleted ones,
        // because the (business_id, asset_code) UNIQUE index still reserves the
        // code for a trashed row. Drop only the business scope (we filter
        // business_id explicitly) and use withTrashed() so we can RESTORE a
        // previously-deleted asset instead of either updating an invisible row
        // or hitting a duplicate-key error trying to re-create it.
        $existing = Asset::withoutGlobalScope(\App\Support\Tenancy\BusinessScope::class)
            ->withTrashed()
            ->where('business_id', $businessId)
            ->where('asset_code', $assetCode)
            ->first();

        // ── Category (required) ───────────────────────────────────────
        $category = $this->findCategory($businessId, $categoryName);
        if (! $category) {
            throw new \RuntimeException("Category '{$categoryName}' not found. Create it under Asset Categories first.");
        }

        // ── Location (optional) ───────────────────────────────────────
        $locationId = null;
        if ($locationName !== '' && $locationName !== '-') {
            $location = $this->findLocation($businessId, $locationName);
            if (! $location) {
                throw new \RuntimeException("Location '{$locationName}' not found. Create it under Asset Locations first, or leave the column blank.");
            }
            $locationId = $location->id;
        }

        // ── AssetModel (optional) — only created if Model OR Manufacturer is present
        $assetModelId = null;
        if ($modelName !== '' || $manufacturer !== '') {
            $resolvedModelName = $modelName !== '' ? $modelName : $manufacturer;
            $resolvedManufacturer = $manufacturer !== '' ? $manufacturer : 'Unknown';

            $model = AssetModel::withoutGlobalScopes()
                ->where('business_id', $businessId)
                ->whereRaw('LOWER(name) = ?', [strtolower($resolvedModelName)])
                ->whereRaw('LOWER(manufacturer) = ?', [strtolower($resolvedManufacturer)])
                ->first();
            if (! $model) {
                // `asset_models.code` is NOT NULL UNIQUE — the manual form
                // makes the user type one, but the import needs to invent it.
                // Use the category prefix so codes stay readable alongside
                // the matching assets (e.g. ELEC-MOD-0001 next to ELEC-0001).
                $model = AssetModel::create([
                    'business_id'  => $businessId,
                    'code'         => $this->generateModelCode($businessId, $category->name),
                    'name'         => $resolvedModelName,
                    'manufacturer' => $resolvedManufacturer,
                    'category_id'  => $category->id,
                ]);
            }
            $assetModelId = $model->id;
        }

        // Optional lookups
        $custodianId = $this->resolveCustodian($businessId, $get('custodian'));
        $vendorId    = $this->resolveVendor($businessId, $get('vendor'));
        $poId        = $this->resolvePurchaseOrder($businessId, $get('po_number'));

        // Honour the Excel "Status" column when it names a real status for this
        // business (e.g. "Faulty / Under Repair" → faulty_under_repair). Null when
        // the cell is blank or doesn't match any status — we then fall back to the
        // custodian-derived default (assigned / in_stock) on create, and on update
        // we simply leave the existing status untouched.
        $resolvedStatus = $this->resolveStatus($businessId, $get('status'));

        // ── Dates / numbers / enums ────────────────────────────────────
        $purchaseDate  = $this->parseDate($get('purchase_date'));
        $warrantyDate  = $this->parseDate($get('warranty_expiry'));
        $insuranceDate = $this->parseDate($get('insurance_expiry'));
        $eolDate       = $this->parseDate($get('end_of_life'));

        $purchaseCost = $this->parseNumber($get('purchase_cost'));
        $salvageValue = $this->parseNumber($get('salvage_value'));

        $usefulLifeRaw = $get('useful_life_yrs') !== '' ? $get('useful_life_yrs') : $get('useful_life');
        $usefulLife = (int) $this->parseNumber($usefulLifeRaw);
        if ($usefulLife <= 0) {
            $usefulLife = 5; // industry default
        }

        $depMethod = strtolower(trim($get('depreciation_method')));
        $depMethod = str_replace([' ', '-'], '_', $depMethod);
        if (! in_array($depMethod, ['straight_line', 'written_down_value', 'units_of_production'], true)) {
            $depMethod = 'straight_line';
        }

        // ── Fields written on BOTH create and update ──────────────────────
        $attributes = [
            'name'                  => $name,
            'serial_number'         => $serial !== '' ? $serial : null,
            'category_id'           => $category->id,
            'asset_model_id'        => $assetModelId,
            'location_id'           => $locationId,
            'current_custodian_id'  => $custodianId,
            'vendor_id'             => $vendorId,
            'purchase_order_id'     => $poId,
            'purchase_date'         => $purchaseDate,
            'purchase_cost'         => $purchaseCost,
            'salvage_value'         => $salvageValue,
            'warranty_expiry_date'  => $warrantyDate,
            'insurance_expiry_date' => $insuranceDate,
            'end_of_life_date'      => $eolDate,
            'depreciation_method'   => $depMethod,
            'useful_life_years'     => $usefulLife,
            'status'                => $resolvedStatus ?? ($custodianId ? 'assigned' : 'in_stock'),
        ];

        if ($existing) {
            // ── UPDATE (skip-empty) ────────────────────────────────────────
            // Only overwrite a field when its source cell actually has a value,
            // so a blank cell LEAVES the existing value untouched instead of
            // wiping it. Name + Category are always present (validated above).
            //
            // Depreciation history (accumulated_depreciation, current_book_value,
            // depreciation_start_date) and assignment-driven status are managed
            // by the assign/depreciate workflows — never touched by the import.
            $hasCell = fn (string $key) => $get($key) !== '' && $get($key) !== '-';

            // field name on the model  =>  whether its source cell is filled
            $present = [
                'name'                  => true, // required, always set
                'category_id'           => true, // required, always set
                'serial_number'         => $hasCell('serial_number'),
                'asset_model_id'        => ($modelName !== '' || $manufacturer !== ''),
                'location_id'           => $hasCell('location'),
                'current_custodian_id'  => $hasCell('custodian'),
                'vendor_id'             => $hasCell('vendor'),
                'purchase_order_id'     => $hasCell('po_number'),
                'purchase_date'         => $hasCell('purchase_date'),
                'purchase_cost'         => $hasCell('purchase_cost'),
                'salvage_value'         => $hasCell('salvage_value'),
                'warranty_expiry_date'  => $hasCell('warranty_expiry'),
                'insurance_expiry_date' => $hasCell('insurance_expiry'),
                'end_of_life_date'      => $hasCell('end_of_life'),
                'depreciation_method'   => $hasCell('depreciation_method'),
                'useful_life_years'     => ($hasCell('useful_life_yrs') || $hasCell('useful_life')),
                // Only overwrite status when the Excel cell named a real status;
                // otherwise leave the asset's current (workflow-driven) status.
                'status'                => $resolvedStatus !== null,
            ];

            $changes = array_filter(
                $attributes,
                fn ($k) => $present[$k] ?? false,
                ARRAY_FILTER_USE_KEY
            );

            // Restore a previously-deleted asset so the re-import brings it
            // back into the live register instead of updating an invisible row.
            if (method_exists($existing, 'trashed') && $existing->trashed()) {
                $existing->restore();
            }

            $existing->fill($changes);
            $existing->updated_by = Auth::guard('admin')->id();
            $existing->save();

            return true;
        }

        // ── CREATE ─────────────────────────────────────────────────────────
        Asset::create($attributes + [
            'business_id'              => $businessId,
            'asset_code'               => $assetCode,
            'depreciation_start_date'  => $purchaseDate,
            'accumulated_depreciation' => 0,
            'current_book_value'       => $purchaseCost,
            // status is already set in $attributes (Excel column → fallback).
            'condition_rating'         => 'good',
            'is_lost'                  => false,
            'is_non_repairable'        => false,
            'created_by'               => Auth::guard('admin')->id(),
        ]);

        return false;
    }

    /**
     * Generate a sequential asset code like "ELEC-0001" when the row leaves Asset Code blank.
     * Falls back to "AST-<n>" if no category prefix is derivable.
     */
    protected function generateAssetCode(int $businessId, string $categoryName): string
    {
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $categoryName) ?: 'AST', 0, 4));

        $last = Asset::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->where('asset_code', 'like', $prefix.'-%')
            ->orderByDesc('id')
            ->value('asset_code');

        $n = 1;
        if ($last && preg_match('/-(\d+)$/', $last, $m)) {
            $n = ((int) $m[1]) + 1;
        }

        return $prefix.'-'.str_pad((string) $n, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate a unique asset-model code like "ELEC-MOD-0001". Mirrors the
     * asset code pattern so models sit alongside their assets in any sort.
     */
    protected function generateModelCode(int $businessId, string $categoryName): string
    {
        $catPrefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $categoryName) ?: 'AST', 0, 4));
        $prefix = $catPrefix.'-MOD';

        $last = AssetModel::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->where('code', 'like', $prefix.'-%')
            ->orderByDesc('id')
            ->value('code');

        $n = 1;
        if ($last && preg_match('/-(\d+)$/', $last, $m)) {
            $n = ((int) $m[1]) + 1;
        }

        return $prefix.'-'.str_pad((string) $n, 4, '0', STR_PAD_LEFT);
    }

    protected function findCategory(int $bid, string $name): ?AssetCategory
    {
        return AssetCategory::withoutGlobalScopes()
            ->where('business_id', $bid)
            ->whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->first();
    }

    protected function findLocation(int $bid, string $name): ?AssetLocation
    {
        return AssetLocation::withoutGlobalScopes()
            ->where('business_id', $bid)
            ->whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->first();
    }

    /**
     * Map the Excel "Status" cell to a real status key for this business.
     * Accepts either the label ("Faulty / Under Repair") or the key
     * ("faulty_under_repair"), case-insensitively. Returns null when the cell
     * is blank or doesn't match any configured status — callers then keep the
     * existing / default status rather than writing an invalid value.
     */
    protected function resolveStatus(int $bid, string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '' || $raw === '-') {
            return null;
        }

        // "Faulty / Under Repair" → "faulty_under_repair"
        $slug = trim(strtolower(preg_replace('/[^a-z0-9]+/i', '_', $raw)), '_');
        $norm = strtolower($raw);

        // NOTE: `key` is a reserved word in MySQL — use the query builder's
        // ->where('key', …) (which back-ticks it safely) and a back-ticked
        // raw clause for the label. The slug is already lower-case and status
        // keys are stored lower-case, so ->where('key', $slug) covers a
        // key-style cell without needing a raw LOWER(`key`) comparison.
        return \App\Models\AssetStatus::withoutGlobalScopes()
            ->where('business_id', $bid)
            ->where(function ($q) use ($slug, $norm) {
                $q->where('key', $slug)
                  ->orWhereRaw('LOWER(`label`) = ?', [$norm]);
            })
            ->value('key');
    }

    protected function resolveCustodian(int $bid, string $name): ?int
    {
        if ($name === '' || $name === '-') {
            return null;
        }

        // Match by full_name or email
        $parts = explode(' ', $name, 2);
        $first = $parts[0];
        $last = $parts[1] ?? null;

        $q = Employee::withoutGlobalScopes()->where('business_id', $bid);
        $q->where(function ($q) use ($name, $first, $last) {
            $q->where('email', $name)
              ->orWhere(function ($q) use ($first, $last) {
                  $q->where('first_name', $first);
                  if ($last !== null) {
                      $q->where('last_name', $last);
                  }
              });
        });

        return $q->value('id');
    }

    protected function resolveVendor(int $bid, string $name): ?int
    {
        if ($name === '' || $name === '-') {
            return null;
        }
        return Vendor::withoutGlobalScopes()
            ->where('business_id', $bid)
            ->whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->value('id');
    }

    protected function resolvePurchaseOrder(int $bid, string $poNumber): ?int
    {
        if ($poNumber === '' || $poNumber === '-') {
            return null;
        }
        return PurchaseOrder::withoutGlobalScopes()
            ->where('business_id', $bid)
            ->where('po_number', $poNumber)
            ->value('id');
    }

    /**
     * Parse a date cell into Y-m-d. Accepts the many shapes a spreadsheet can
     * produce so users don't have to reformat their file:
     *   - ISO:        2031-01-15
     *   - Day-first:  15/01/2031, 15-01-2031, 15.01.2031
     *   - Excel date serial number: 47500  (what Excel stores under the hood)
     *   - Anything else Carbon can read (e.g. "15 Jan 2031")
     */
    protected function parseDate(string $s): ?string
    {
        $s = trim($s);
        if ($s === '' || $s === '-') {
            return null;
        }

        // Excel stores dates as a serial number of days since 1899-12-30.
        // When the cell isn't text-formatted it reaches us as that number.
        if (preg_match('/^\d{4,6}(\.\d+)?$/', $s)) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $s)
                    ->format('Y-m-d');
            } catch (Throwable) {
                // fall through to text parsing
            }
        }

        // Day-first formats (dd/mm/yyyy, dd-mm-yyyy, dd.mm.yyyy). Carbon::parse
        // would otherwise read these as month-first and misparse/throw.
        if (preg_match('#^(\d{1,2})[/.\-](\d{1,2})[/.\-](\d{4})$#', $s, $m)) {
            $day = (int) $m[1];
            $month = (int) $m[2];
            $year = (int) $m[3];
            if ($day >= 1 && $day <= 31 && $month >= 1 && $month <= 12) {
                try {
                    return Carbon::createFromDate($year, $month, $day)->toDateString();
                } catch (Throwable) {
                    return null;
                }
            }
        }

        try {
            return Carbon::parse($s)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    protected function parseNumber(string $s): float
    {
        if ($s === '' || $s === '-') {
            return 0.0;
        }
        $clean = preg_replace('/[^0-9.\-]/', '', $s);
        return (float) ($clean ?: 0);
    }
}
