<?php

namespace App\Exports;

use App\Models\Employee;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Exports the Employee list, honouring the same search / department /
 * designation / status filters as the on-screen list.
 */
class EmployeeReportExport implements FromCollection, WithHeadings
{
    public function __construct(private array $filters = [])
    {
    }

    public function collection()
    {
        return Employee::with(['department', 'designation', 'shift', 'reportingManager'])
            ->when($this->filters['search'] ?? null, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('first_name', 'like', "%{$s}%")
                    ->orWhere('last_name', 'like', "%{$s}%")
                    ->orWhere('employee_code', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%");
            }))
            ->when($this->filters['department_id'] ?? null, fn ($q, $id) => $q->where('department_id', $id))
            ->when($this->filters['designation_id'] ?? null, fn ($q, $id) => $q->where('designation_id', $id))
            ->when($this->filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->get()
            ->map(fn (Employee $e) => [
                $e->employee_code,
                trim($e->first_name.' '.$e->last_name),
                $e->email,
                $e->phone,
                $e->department?->name,
                $e->designation?->name,
                $e->shift?->name,
                $e->reportingManager ? trim($e->reportingManager->first_name.' '.$e->reportingManager->last_name) : null,
                ucfirst(str_replace('_', ' ', (string) $e->status)),
                optional($e->joining_date)->format('Y-m-d'),
                optional($e->probation_end_date)->format('Y-m-d'),
                optional($e->confirmation_date)->format('Y-m-d'),
                ucfirst(str_replace('_', ' ', (string) $e->employment_type)),
                $e->gender,
            ]);
    }

    public function headings(): array
    {
        return [
            'Employee Code', 'Name', 'Email', 'Phone', 'Department', 'Designation',
            'Shift', 'Reporting Manager', 'Status', 'Joining Date', 'Probation End',
            'Confirmation Date', 'Employment Type', 'Gender',
        ];
    }
}
