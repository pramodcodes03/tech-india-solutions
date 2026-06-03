<?php

namespace App\Exports;

use App\Exports\AssetImportTemplate\AssetSheet;
use App\Exports\AssetImportTemplate\RefSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Downloadable .xlsx workbook for the asset bulk-import flow.
 *
 * Sheet 1 (Assets):       the data-entry sheet — headers, two example rows,
 *                         and in-cell dropdowns on Category / Location /
 *                         Vendor / Custodian / Depreciation Method.
 * Sheets 2–5 (reference): one-column lists used as dropdown sources.
 *                         Visible so the user can see what's pickable;
 *                         protected so they can't accidentally break the
 *                         linked validation by deleting a row.
 *
 * Reference data is supplied by the controller, which reads the active
 * business's masters at request time so the dropdowns are always fresh.
 */
class AssetImportTemplateExport implements WithMultipleSheets
{
    public function __construct(
        protected array $categories,
        protected array $locations = [],
        protected array $vendors = [],
        protected array $custodians = [],
    ) {}

    public function sheets(): array
    {
        // Each reference sheet registers a workbook-level named range whose
        // name matches the constants the AssetSheet validations reference.
        return [
            new AssetSheet($this->categories, $this->locations, $this->vendors, $this->custodians),
            new RefSheet('Categories', 'Category', $this->categories, AssetSheet::DN_CATEGORIES),
            new RefSheet('Locations', 'Location', $this->locations, AssetSheet::DN_LOCATIONS),
            new RefSheet('Custodians', 'Custodian (Full Name)', $this->custodians, AssetSheet::DN_CUSTODIANS),
            new RefSheet('Vendors', 'Vendor', $this->vendors, AssetSheet::DN_VENDORS),
        ];
    }
}
