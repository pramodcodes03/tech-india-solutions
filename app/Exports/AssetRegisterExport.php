<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AssetRegisterExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    public function __construct(protected $assets) {}

    public function collection()
    {
        return $this->assets;
    }

    public function headings(): array
    {
        return [
            'Asset Code', 'Name', 'Serial Number', 'Category', 'Model', 'Manufacturer',
            'Location', 'Custodian', 'Vendor', 'PO Number',
            'Purchase Date', 'Purchase Cost (₹)', 'Salvage Value (₹)',
            'Warranty Expiry', 'Insurance Expiry', 'End of Life',
            'Depreciation Method', 'Useful Life (yrs)',
            'Accumulated Depreciation (₹)', 'Current Book Value (₹)',
            'Status', 'Condition', 'Lost?',
        ];
    }

    public function map($a): array
    {
        return [
            $a->asset_code,
            $a->name,
            $a->serial_number ?: '-',
            $a->category?->name ?: '-',
            $a->model?->name ?: '-',
            $a->model?->manufacturer ?: '-',
            $a->location?->name ?: '-',
            $a->custodian?->full_name ?: '-',
            $a->vendor?->name ?: '-',
            $a->purchaseOrder?->po_number ?: '-',
            $a->purchase_date?->format('Y-m-d') ?: '-',
            (float) $a->purchase_cost,
            (float) $a->salvage_value,
            $a->warranty_expiry_date?->format('Y-m-d') ?: '-',
            $a->insurance_expiry_date?->format('Y-m-d') ?: '-',
            $a->end_of_life_date?->format('Y-m-d') ?: '-',
            str_replace('_', ' ', $a->depreciation_method),
            (int) $a->useful_life_years,
            (float) $a->accumulated_depreciation,
            (float) $a->current_book_value,
            $a->status_label,
            ucfirst($a->condition_rating),
            $a->is_lost ? 'YES' : 'no',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                  'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '122E6D']]],
        ];
    }
}
