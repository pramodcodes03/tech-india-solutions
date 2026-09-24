<?php

namespace App\Exports;

use App\Services\LeaveRequestReportService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * The Leave Requests spreadsheet.
 *
 * Holds no query of its own — rows and headings both come from
 * LeaveRequestReportService, so the spreadsheet, the PDF and the screen can
 * never disagree about what a filter means or which columns exist.
 */
class LeaveRequestsExport implements FromArray, ShouldAutoSize, WithHeadings
{
    public function __construct(
        private LeaveRequestReportService $report,
        private array $filters = [],
    ) {}

    public function array(): array
    {
        return $this->report->rows($this->filters);
    }

    public function headings(): array
    {
        return LeaveRequestReportService::HEADINGS;
    }
}
