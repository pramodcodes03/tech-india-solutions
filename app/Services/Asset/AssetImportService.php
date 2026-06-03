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
                    $this->importRow($row, $headerMap, $businessId, $seenCodes);
                    $imported++;
                } catch (Throwable $e) {
                    $failed++;
                    $errors[] = ['row' => $rowNum, 'message' => $e->getMessage()];
                }
            }
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            $errors[] = ['row' => 0, 'message' => 'Import aborted: '.$e->getMessage()];
            return ['imported' => 0, 'failed' => $failed, 'errors' => $errors];
        }

        return ['imported' => $imported, 'failed' => $failed, 'errors' => $errors];
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
     * Insert one asset row. Throws on any validation / lookup failure;
     * the caller catches and converts into a row error.
     */
    protected function importRow(array $row, array $map, int $businessId, array &$seenCodes): void
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

        $exists = Asset::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->where('asset_code', $assetCode)
            ->exists();
        if ($exists) {
            throw new \RuntimeException("Asset Code '{$assetCode}' already exists for this business.");
        }

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

        // ── Insert ─────────────────────────────────────────────────────
        Asset::create([
            'business_id'             => $businessId,
            'asset_code'              => $assetCode,
            'name'                    => $name,
            'serial_number'           => $serial !== '' ? $serial : null,
            'category_id'             => $category->id,
            'asset_model_id'          => $assetModelId,
            'location_id'             => $locationId,
            'current_custodian_id'    => $custodianId,
            'vendor_id'               => $vendorId,
            'purchase_order_id'       => $poId,
            'purchase_date'           => $purchaseDate,
            'purchase_cost'           => $purchaseCost,
            'salvage_value'           => $salvageValue,
            'warranty_expiry_date'    => $warrantyDate,
            'insurance_expiry_date'   => $insuranceDate,
            'end_of_life_date'        => $eolDate,
            'depreciation_method'     => $depMethod,
            'useful_life_years'       => $usefulLife,
            'depreciation_start_date' => $purchaseDate,
            'accumulated_depreciation' => 0,
            'current_book_value'      => $purchaseCost,
            'status'                  => $custodianId ? 'assigned' : 'in_stock',
            'condition_rating'        => 'good',
            'is_lost'                 => false,
            'is_non_repairable'       => false,
            'created_by'              => Auth::guard('admin')->id(),
        ]);
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

    protected function parseDate(string $s): ?string
    {
        if ($s === '' || $s === '-') {
            return null;
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
