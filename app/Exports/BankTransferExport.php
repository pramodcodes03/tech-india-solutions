<?php

namespace App\Exports;

use App\Models\Payslip;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Salary bank-upload format: one row per employee with their bank account,
 * IFSC and net pay for the month — ready to feed a bank's bulk-transfer file.
 */
class BankTransferExport implements FromCollection, WithHeadings
{
    public function __construct(private int $month, private int $year)
    {
    }

    public function collection()
    {
        return Payslip::with('employee')
            ->where('month', $this->month)->where('year', $this->year)
            ->whereIn('status', ['generated', 'approved', 'paid'])
            ->get()
            ->map(fn (Payslip $p) => [
                $p->employee?->employee_code,
                $p->employee?->full_name,
                $p->employee?->bank_account_number,
                $p->employee?->bank_ifsc,
                $p->employee?->bank_name,
                number_format((float) $p->net_pay, 2, '.', ''),
            ]);
    }

    public function headings(): array
    {
        return ['Employee Code', 'Beneficiary Name', 'Account Number', 'IFSC', 'Bank', 'Amount'];
    }
}
