<?php

namespace App\Services\Import;

use App\Models\Employee;
use App\Models\LeaveType;
use App\Services\LeaveService;

class LeaveBalanceImporter implements RowImporter
{
    public function key(): string
    {
        return 'leave_balances';
    }

    public function label(): string
    {
        return 'Leave Balances';
    }

    public function permission(): string
    {
        return 'leaves.approve';
    }

    public function templateHeaders(): array
    {
        return ['Employee Code', 'Leave Type Code', 'Year', 'Allocated', 'Carried Forward'];
    }

    public function sampleRow(): array
    {
        return ['EMP-0001', 'EL', date('Y'), '18', '6'];
    }

    public function validateRow(array $row, int $businessId): array
    {
        $errors = [];
        if (! Employee::where('employee_code', trim($row['employee code'] ?? ''))->exists()) {
            $errors[] = 'Unknown employee code.';
        }
        if (! LeaveType::where('code', trim($row['leave type code'] ?? ''))->exists()) {
            $errors[] = 'Unknown leave type code.';
        }
        if (! is_numeric($row['allocated'] ?? '')) {
            $errors[] = 'Allocated must be numeric.';
        }

        return $errors;
    }

    public function importRow(array $row, int $businessId): void
    {
        $employee = Employee::where('employee_code', trim($row['employee code']))->firstOrFail();
        $type = LeaveType::where('code', trim($row['leave type code']))->firstOrFail();

        app(LeaveService::class)->setBalance(
            $employee->id,
            $type->id,
            (int) $row['year'],
            [
                'allocated' => (float) $row['allocated'],
                'carried_forward' => (float) ($row['carried forward'] ?? 0),
            ]
        );
    }
}
