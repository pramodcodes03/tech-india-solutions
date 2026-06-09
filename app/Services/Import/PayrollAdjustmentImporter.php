<?php

namespace App\Services\Import;

use App\Models\Employee;
use App\Models\PayrollAdjustment;
use Illuminate\Support\Facades\Auth;

class PayrollAdjustmentImporter implements RowImporter
{
    public function key(): string
    {
        return 'payroll_adjustments';
    }

    public function label(): string
    {
        return 'Payroll Adjustments';
    }

    public function permission(): string
    {
        return 'payroll_adjustments.manage';
    }

    public function templateHeaders(): array
    {
        return ['Employee Code', 'Month', 'Year', 'Component', 'Amount', 'Note'];
    }

    public function sampleRow(): array
    {
        return ['EMP-0001', date('n'), date('Y'), 'incentive', '5000', 'Q2 performance incentive'];
    }

    public function validateRow(array $row, int $businessId): array
    {
        $errors = [];
        $code = trim($row['employee code'] ?? '');
        if ($code === '' || ! Employee::where('employee_code', $code)->exists()) {
            $errors[] = "Unknown employee code: {$code}";
        }
        $component = strtolower(trim($row['component'] ?? ''));
        if (! array_key_exists($component, PayrollAdjustment::COMPONENTS)) {
            $errors[] = "Invalid component: {$component} (incentive/arrears/bonus/extra_deduction)";
        }
        if (! is_numeric(str_replace(',', '', $row['amount'] ?? ''))) {
            $errors[] = 'Amount must be numeric.';
        }
        $m = (int) ($row['month'] ?? 0);
        if ($m < 1 || $m > 12) {
            $errors[] = 'Month must be 1–12.';
        }

        return $errors;
    }

    public function importRow(array $row, int $businessId): void
    {
        $employee = Employee::where('employee_code', trim($row['employee code']))->firstOrFail();

        PayrollAdjustment::create([
            'business_id' => $businessId,
            'employee_id' => $employee->id,
            'month' => (int) $row['month'],
            'year' => (int) $row['year'],
            'component' => strtolower(trim($row['component'])),
            'amount' => (float) str_replace(',', '', $row['amount']),
            'note' => trim($row['note'] ?? '') ?: null,
            'created_by' => Auth::guard('admin')->id(),
        ]);
    }
}
