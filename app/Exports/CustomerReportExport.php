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
        return [
            $row->customer_code,
            $row->name,
            $row->company_name ?? '-',
            $row->total_orders ?? 0,
            number_format($row->total_invoiced ?? 0, 2),
            number_format($row->amount_paid ?? 0, 2),
            number_format($row->outstanding_balance ?? 0, 2),
        ];
    }
}
