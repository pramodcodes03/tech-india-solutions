<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AssetAssignmentsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    public function __construct(protected $assignments) {}

    public function collection()
    {
        return $this->assignments;
    }

    public function headings(): array
    {
        return [
            'Code', 'Action', 'Asset Code', 'Asset Name',
            'Employee', 'Employee Code',
            'From Location', 'To Location',
            'Assigned On', 'Returned On',
            'Condition (Assign)', 'Condition (Return)',
            'Notes', 'Return Notes',
        ];
    }

    public function map($a): array
    {
        return [
            $a->assignment_code,
            ucfirst($a->action_type),
            $a->asset?->asset_code,
            $a->asset?->name,
            $a->employee?->full_name ?: '-',
            $a->employee?->employee_code ?: '-',
            $a->fromLocation?->name ?: '-',
            $a->toLocation?->name ?: '-',
            $a->assigned_at?->format('Y-m-d') ?: '-',
            $a->returned_at?->format('Y-m-d') ?: '-',
            $a->condition_at_assign ? ucfirst($a->condition_at_assign) : '-',
            $a->condition_at_return ? ucfirst($a->condition_at_return) : '-',
            $a->notes ?: '-',
            $a->return_notes ?: '-',
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
