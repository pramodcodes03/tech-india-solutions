<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EmployeeService
{
    public function generateCode(): string
    {
        $prefix = app(\App\Support\Tenancy\CurrentBusiness::class)->get()?->employee_code_prefix ?? 'EMP';
        $last = Employee::withTrashed()
            ->where('employee_code', 'like', $prefix.'%')
            ->orderByDesc('employee_code')
            ->first();

        $next = $last ? (int) substr($last->employee_code, strlen($prefix)) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function create(array $data): Employee
    {
        return DB::transaction(function () use ($data) {
            $data['employee_code'] = $this->generateCode();
            $data['created_by'] = Auth::guard('admin')->id();

            // Default password is the employee code (employee must change on first login)
            if (empty($data['password'])) {
                $data['password'] = $data['employee_code'];
            }

            $employee = Employee::create($data);

            // Provision leave balances for the current year
            $this->allocateAnnualLeaves($employee, (int) date('Y'));

            return $employee;
        });
    }

    public function update(Employee $employee, array $data): Employee
    {
        $data['updated_by'] = Auth::guard('admin')->id();

        // Only update password if explicitly provided
        if (empty($data['password'])) {
            unset($data['password']);
        }

        $employee->update($data);

        return $employee->refresh();
    }

    public function delete(Employee $employee): void
    {
        $employee->update(['deleted_by' => Auth::guard('admin')->id(), 'status' => 'inactive']);
        $employee->delete();
    }

    public function resetPassword(Employee $employee, ?string $newPassword = null): string
    {
        $password = $newPassword ?: $employee->employee_code;
        $employee->update(['password' => Hash::make($password)]);

        return $password;
    }

    /**
     * Allocate annual leave quotas to an employee for a given year.
     * Prorates based on joining date if joined mid-year.
     */
    public function allocateAnnualLeaves(Employee $employee, int $year): void
    {
        $types = LeaveType::where('status', 'active')->get();

        $joinedThisYear = $employee->joining_date && $employee->joining_date->year === $year;
        $monthsWorked = $joinedThisYear
            ? max(1, 12 - $employee->joining_date->month + 1)
            : 12;

        foreach ($types as $type) {
            $allocated = $type->annual_quota > 0
                ? round(($type->annual_quota * $monthsWorked) / 12, 1)
                : 0;

            LeaveBalance::updateOrCreate(
                ['employee_id' => $employee->id, 'leave_type_id' => $type->id, 'year' => $year],
                ['allocated' => $allocated]
            );
        }
    }
}
