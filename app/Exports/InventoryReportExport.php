<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InventoryReportExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return ['Product Code', 'Product Name', 'Category', 'Warehouse', 'Current Stock', 'Reorder Level'];
    }

    public function map($row): array
    {
        return [
            $row->code ?? '-',
            $row->name,
            $row->category->name ?? '-',
            '-',
            $row->current_stock,
            $row->reorder_level ?? 0,
        ];
    }
}
