<?php

namespace App\Exports\AssetImportTemplate;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Main data-entry sheet of the asset-import template. Headers + two example
 * rows + in-cell dropdowns wired to the reference sheets in the same workbook.
 *
 * The dropdowns are advisory (error style = INFORMATION) so users can still
 * paste bulk values and only see a soft warning if a value is off-list —
 * mirrors the importer's behaviour, which matches by name case-insensitively.
 */
class AssetSheet implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle, WithEvents
{
    /**
     * Workbook-level named ranges the reference sheets register. Validation
     * formulas reference these by name — Google Sheets and Excel both import
     * named-range references reliably, while raw cross-sheet references
     * (e.g. 'Categories'!$A$2:$A$3) are silently dropped by Google Sheets
     * on .xlsx import.
     */
    public const DN_CATEGORIES = 'AssetCategoryList';
    public const DN_LOCATIONS  = 'AssetLocationList';
    public const DN_CUSTODIANS = 'AssetCustodianList';
    public const DN_VENDORS    = 'AssetVendorList';

    public function __construct(
        protected array $categories,
        protected array $locations,
        protected array $vendors,
        protected array $custodians,
    ) {}

    public function array(): array
    {
        // Use a real category for the dummy rows so the sample actually
        // imports cleanly if the user runs it as-is.
        $sampleCategory = $this->categories[0] ?? 'Electronics';

        return [
            // Fully-filled example
            [
                'AST-0001', 'Dell Latitude 5420', 'DL-12345', $sampleCategory, 'Latitude 5420', 'Dell',
                $this->locations[0] ?? '', $this->custodians[0] ?? '', $this->vendors[0] ?? '', '',
                '2026-01-15', '65000', '5000',
                '2027-01-15', '', '2031-01-15',
                'straight_line', '5',
            ],
            // Minimal example — only Name + Category populated
            [
                '', 'Wooden Desk', '', $sampleCategory, '', '',
                '', '', '', '',
                '', '', '',
                '', '', '',
                '', '',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'Asset Code', 'Name', 'Serial Number', 'Category', 'Model', 'Manufacturer',
            'Location', 'Custodian', 'Vendor', 'PO Number',
            'Purchase Date', 'Purchase Cost', 'Salvage Value',
            'Warranty Expiry', 'Insurance Expiry', 'End of Life',
            'Depreciation Method', 'Useful Life (yrs)',
        ];
    }

    public function title(): string
    {
        return 'Assets';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->freezePane('A2');

        // Header row
        $sheet->getStyle('A1:R1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '122E6D']],
        ]);

        // Mandatory header cells (Name=B, Category=D) get an accent
        foreach (['B1', 'D1'] as $c) {
            $sheet->getStyle($c)->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D97706']],
            ]);
        }

        // Sample rows in italic gray so users know they're examples
        $sheet->getStyle('A2:R3')->applyFromArray([
            'font' => ['color' => ['rgb' => '6B7280'], 'italic' => true],
        ]);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Always-available fixed enum: Depreciation Method (column Q)
                $this->applyListValidation(
                    $sheet,
                    'Q2:Q1000',
                    '"straight_line,written_down_value,units_of_production"',
                    'Pick a depreciation method',
                    'Use straight_line, written_down_value, or units_of_production.',
                );

                // Category (mandatory) — column D — always populated by the
                // pre-flight check in the controller, so this list is never empty.
                $this->applyListValidation($sheet, 'D2:D1000',
                    self::DN_CATEGORIES,
                    'Pick a category',
                    'Category must match one created under Asset Categories.');

                // Optional dropdowns — skipped silently when the master list
                // is empty so the column stays free-text instead of forcing a
                // value that doesn't exist yet.
                if (! empty($this->locations)) {
                    $this->applyListValidation($sheet, 'G2:G1000', self::DN_LOCATIONS,
                        'Pick a location', 'Location must match one created under Asset Locations.');
                }

                if (! empty($this->custodians)) {
                    $this->applyListValidation($sheet, 'H2:H1000', self::DN_CUSTODIANS,
                        'Pick a custodian', 'Custodian must match an active employee.');
                }

                if (! empty($this->vendors)) {
                    $this->applyListValidation($sheet, 'I2:I1000', self::DN_VENDORS,
                        'Pick a vendor', 'Vendor must match one created under Vendors.');
                }
            },
        ];
    }

    protected function applyListValidation(Worksheet $sheet, string $range, string $formula1, string $prompt, string $error): void
    {
        $validation = new DataValidation;
        $validation->setType(DataValidation::TYPE_LIST);
        // INFORMATION (not STOP) — soft warning, lets pasted bulk values through.
        $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
        $validation->setAllowBlank(true);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setShowDropDown(true);
        $validation->setPromptTitle('Pick from list');
        $validation->setPrompt($prompt);
        $validation->setErrorTitle('Not in list');
        $validation->setError($error);
        $validation->setFormula1($formula1);

        $sheet->setDataValidation($range, $validation);
    }
}
