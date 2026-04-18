<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CustomerReportExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
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
        return ['Customer Code', 'Name', 'Company', 'Total Orders', 'Total Invoiced', 'Amount Paid', 'Outstanding Balance'];
    }

    public function map($row): array
    {
        $invoiced = $row->total_invoiced ?? 0;
        $paid     = $row->total_paid ?? 0;
        return [
            $row->code ?? '-',
            $row->name,
            $row->company ?? '-',
            $row->invoices->count(),
            number_format($invoiced, 2),
            number_format($paid, 2),
            number_format($invoiced - $paid, 2),
        ];
    }
}
