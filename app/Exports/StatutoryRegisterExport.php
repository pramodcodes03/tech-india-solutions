<?php

namespace App\Exports;

use App\Services\StatutoryService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Monthly statutory register. $type selects the columns: pf, esi, pt, lwf or
 * all. PF/ESI variants double as the challan source files.
 */
class StatutoryRegisterExport implements FromCollection, WithHeadings
{
    public function __construct(
        private int $month,
        private int $year,
        private string $type = 'all',
    ) {
    }

    public function collection()
    {
        $rows = app(StatutoryService::class)->register($this->month, $this->year);

        return $rows->map(function (array $r) {
            return match ($this->type) {
                'pf' => [$r['employee_code'], $r['employee_name'], $r['uan'], $r['basic'], $r['pf_employee'], $r['pf_employer_epf'], $r['eps']],
                'esi' => [$r['employee_code'], $r['employee_name'], $r['esi_number'], $r['gross'], $r['esi_employee'], $r['esi_employer']],
                'pt' => [$r['employee_code'], $r['employee_name'], $r['gross'], $r['pt']],
                'lwf' => [$r['employee_code'], $r['employee_name'], $r['lwf_employee'], $r['lwf_employer']],
                default => [$r['employee_code'], $r['employee_name'], $r['gross'], $r['pf_employee'], $r['pf_employer_epf'], $r['eps'], $r['esi_employee'], $r['esi_employer'], $r['pt'], $r['lwf_employee'], $r['lwf_employer'], $r['tds']],
            };
        });
    }

    public function headings(): array
    {
        return match ($this->type) {
            'pf' => ['Code', 'Name', 'UAN', 'Basic', 'PF Employee', 'EPF Employer', 'EPS'],
            'esi' => ['Code', 'Name', 'ESI No.', 'Gross', 'ESI Employee', 'ESI Employer'],
            'pt' => ['Code', 'Name', 'Gross', 'Professional Tax'],
            'lwf' => ['Code', 'Name', 'LWF Employee', 'LWF Employer'],
            default => ['Code', 'Name', 'Gross', 'PF Emp', 'EPF Er', 'EPS', 'ESI Emp', 'ESI Er', 'PT', 'LWF Emp', 'LWF Er', 'TDS'],
        };
    }
}
