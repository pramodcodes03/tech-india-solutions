<?php

namespace App\Exports;

use App\Models\Attendance;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Exports the Daily Attendance list for a given date, honouring the same
 * department / status / search filters as the on-screen page.
 */
class DailyAttendanceExport implements FromCollection, WithHeadings
{
    public function __construct(private string $date, private array $filters = [])
    {
    }

    public function collection()
    {
        return Attendance::with('employee.department', 'employee.designation')
            ->whereDate('date', $this->date)
            ->when($this->filters['department_id'] ?? null, fn ($q, $id) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $id)))
            ->when($this->filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($this->filters['search'] ?? null, fn ($q, $s) => $q->whereHas('employee', fn ($e) => $e->where(function ($q) use ($s) {
                $q->where('first_name', 'like', "%{$s}%")
                    ->orWhere('last_name', 'like', "%{$s}%")
                    ->orWhere('employee_code', 'like', "%{$s}%")
                    ->orWhere('card_no', 'like', "%{$s}%");
            })))
            ->orderBy('employee_id')
            ->get()
            ->map(fn (Attendance $a) => [
                $a->employee?->employee_code,
                trim(($a->employee?->first_name ?? '').' '.($a->employee?->last_name ?? '')),
                $a->employee?->department?->name,
                \Carbon\Carbon::parse($a->date)->format('Y-m-d'),
                $a->check_in,
                $a->check_out,
                $a->hours_worked,
                ucwords(str_replace('_', ' ', (string) $a->status)),
                $a->source,
            ]);
    }

    public function headings(): array
    {
        return ['Employee Code', 'Employee', 'Department', 'Date', 'Check-in', 'Check-out', 'Hours', 'Status', 'Source'];
    }
}
