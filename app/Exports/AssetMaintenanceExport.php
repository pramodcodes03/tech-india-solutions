<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AssetMaintenanceExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    public function __construct(protected $logs) {}

    public function collection()
    {
        return $this->logs;
    }

    public function headings(): array
    {
        return [
            'Log Code', 'Asset Code', 'Asset Name', 'Category',
            'Type', 'Status', 'Scheduled Date', 'Performed Date',
            'Technician', 'Vendor',
            'Parts Cost (₹)', 'Labour Cost (₹)', 'Total Cost (₹)',
            'Downtime (hrs)',
            'Description', 'Parts Used', 'Resolution Notes',
        ];
    }

    public function map($l): array
    {
        return [
            $l->log_code,
            $l->asset?->asset_code,
            $l->asset?->name,
            $l->asset?->category?->name ?: '-',
            ucfirst($l->type),
            ucwords(str_replace('_', ' ', $l->status)),
            $l->scheduled_date?->format('Y-m-d') ?: '-',
            $l->performed_date?->format('Y-m-d') ?: '-',
            $l->technician?->full_name ?: $l->performed_by ?: '-',
            $l->vendor_name ?: '-',
            (float) $l->parts_cost,
            (float) $l->labour_cost,
            (float) $l->total_cost,
            (float) $l->downtime_hours,
            $l->description ?: '-',
            $l->parts_used ?: '-',
            $l->resolution_notes ?: '-',
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
