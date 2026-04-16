<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PaymentReportExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
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
        return ['Payment #', 'Date', 'Customer', 'Invoice #', 'Amount', 'Mode', 'Reference'];
    }

    public function map($row): array
    {
        return [
            $row->payment_number,
            $row->payment_date,
            $row->customer->name ?? '-',
            $row->invoice->invoice_number ?? '-',
            number_format($row->amount, 2),
            $row->payment_mode,
            $row->reference ?? '-',
        ];
    }
}
