<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PurchaseReportExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
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
        return ['PO #', 'Vendor', 'PO Date', 'Grand Total', 'Status'];
    }

    public function map($row): array
    {
        return [
            $row->po_number,
            $row->vendor->name ?? '-',
            $row->po_date,
            number_format($row->grand_total, 2),
            $row->status,
        ];
    }
}
