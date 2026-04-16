<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SalesReportExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
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
        return ['Date', 'Invoice #', 'Customer', 'Grand Total', 'Status'];
    }

    public function map($row): array
    {
        return [
            $row->invoice_date,
            $row->invoice_number,
            $row->customer->name ?? '-',
            number_format($row->grand_total, 2),
            $row->status,
        ];
    }
}
