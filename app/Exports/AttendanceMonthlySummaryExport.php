<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Exports the same data the "Monthly Summary" page renders, minus pagination.
 *
 * The controller pre-computes monthlySummary() per employee and pairs each
 * Employee model with its summary array as a `['employee' => …, 'summary' => …]`
 * tuple, so this class stays purely about formatting / styling and contains
 * no business logic of its own.
 */
class AttendanceMonthlySummaryExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    /**
     * @param  Collection<int, array{employee: \App\Models\Employee, summary: array}>  $rows
     */
    public function __construct(
        protected Collection $rows,
        protected int $month,
        protected int $year,
    ) {}

    public function collection()
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Employee Code', 'Employee Name', 'Department',
            'Working Days', 'Present', 'Absent', 'Half-Day', 'Late', 'On Leave',
            'Holidays', 'Week Offs', 'Comp Offs',
            'Paid Days', 'LOP Days',
            'Paid Leave Days', 'Unpaid Leave Days',
        ];
    }

    public function map($row): array
    {
        $e = $row['employee'];
        $s = $row['summary'] ?? [];

        // Cast every numeric to string. PhpSpreadsheet drops cells whose
        // value is integer 0 from the rendered output entirely — passing
        // strings forces 0 to render as a literal "0" in both CSV and XLSX
        // so MIS sees a value in every cell.
        $intStr   = fn ($v) => (string) (int) ($v ?? 0);
        $floatStr = fn ($v) => rtrim(rtrim(number_format((float) ($v ?? 0), 2, '.', ''), '0'), '.') ?: '0';

        return [
            $e->employee_code ?: '-',
            trim(($e->first_name ?? '').' '.($e->last_name ?? '')) ?: '-',
            $e->department?->name ?: '-',
            $intStr($s['working_days']      ?? 0),
            $intStr($s['present']           ?? 0),
            $intStr($s['absent']            ?? 0),
            $intStr($s['half_day']          ?? 0),
            $intStr($s['late']              ?? 0),
            $intStr($s['on_leave']          ?? 0),
            $intStr($s['holidays']          ?? 0),
            $intStr($s['fixed_week_offs']   ?? 0),
            $intStr($s['dynamic_week_offs'] ?? 0),
            $floatStr($s['paid_days']         ?? 0),
            $floatStr($s['lop_days']          ?? 0),
            $floatStr($s['paid_leave_days']   ?? 0),
            $floatStr($s['unpaid_leave_days'] ?? 0),
        ];
    }

    public function title(): string
    {
        return \Carbon\Carbon::createFromDate($this->year, $this->month, 1)->format('F Y');
    }

    public function styles(Worksheet $sheet)
    {
        $lastCol = 'P'; // 16 columns
        $sheet->freezePane('A2');

        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '122E6D']],
        ]);

        return [];
    }
}
