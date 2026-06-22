<?php

namespace App\Services\Import;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Services\EmployeeService;

class EmployeeImporter implements RowImporter
{
    public function key(): string
    {
        return 'employees';
    }

    public function label(): string
    {
        return 'Employees';
    }

    public function permission(): string
    {
        return 'employees.create';
    }

    public function templateHeaders(): array
    {
        return ['Employee Code', 'First Name', 'Last Name', 'Email', 'Phone', 'Department', 'Designation', 'Joining Date', 'Employment Type'];
    }

    public function sampleRow(): array
    {
        return ['EMP001', 'Asha', 'Verma', 'asha@example.com', '9876543210', 'Engineering', 'Software Engineer', '2026-06-01', 'full_time'];
    }

    /** Map free-text employment type to the employees enum, default full_time. */
    private function normalizeEmploymentType(?string $value): string
    {
        $v = strtolower(trim((string) $value));
        $v = str_replace([' ', '-'], '_', $v);
        $map = ['permanent' => 'full_time', 'fulltime' => 'full_time', 'parttime' => 'part_time', 'temp' => 'contract', 'temporary' => 'contract', 'trainee' => 'intern'];
        $v = $map[$v] ?? $v;

        return in_array($v, ['full_time', 'part_time', 'contract', 'intern'], true) ? $v : 'full_time';
    }

    public function validateRow(array $row, int $businessId): array
    {
        $errors = [];
        if (empty(trim($row['first name'] ?? ''))) {
            $errors[] = 'First Name is required.';
        }

        $code = trim($row['employee code'] ?? '');
        $overwrite = ! empty($row['__overwrite']);
        // The existing employee this row would overwrite (matched on code).
        $existing = $code !== '' ? Employee::where('employee_code', $code)->first() : null;

        // A duplicate code without the Overwrite option is a hard error so we
        // never silently create a second employee with the same code.
        if ($existing && ! $overwrite) {
            $errors[] = "Employee Code {$code} already exists — tick Overwrite to update it.";
        }

        $email = trim($row['email'] ?? '');
        if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email.';
        }
        // Email must be unique — but the employee being overwritten may keep its
        // own email, so exclude it from the clash check.
        if ($email !== '') {
            $clash = Employee::where('email', $email)
                ->when($existing, fn ($q) => $q->where('id', '!=', $existing->id))
                ->exists();
            if ($clash) {
                $errors[] = "Email {$email} already exists.";
            }
        }

        $jd = trim($row['joining date'] ?? '');
        if ($jd !== '' && ! strtotime($jd)) {
            $errors[] = 'Invalid joining date.';
        }

        return $errors;
    }

    public function importRow(array $row, int $businessId): void
    {
        $code = trim($row['employee code'] ?? '');
        $overwrite = ! empty($row['__overwrite']);
        $existing = $code !== '' ? Employee::where('employee_code', $code)->first() : null;

        $deptId = $this->resolve(Department::class, $row['department'] ?? null);
        $desigId = $this->resolve(Designation::class, $row['designation'] ?? null);

        // Common attributes from the row (blank cells become null).
        $attrs = [
            'first_name' => trim($row['first name']),
            'last_name' => trim($row['last name'] ?? '') ?: null,
            'email' => trim($row['email'] ?? '') ?: null,
            'phone' => trim($row['phone'] ?? '') ?: null,
            'department_id' => $deptId,
            'designation_id' => $desigId,
            'joining_date' => ! empty($row['joining date']) ? date('Y-m-d', strtotime($row['joining date'])) : null,
            'employment_type' => $this->normalizeEmploymentType($row['employment type'] ?? null),
        ];

        if ($existing && $overwrite) {
            // UPDATE the existing employee. Skip blank cells so an empty column
            // never wipes an existing value; never touch code / password / status.
            $changes = array_filter($attrs, fn ($v) => $v !== null && $v !== '');
            app(EmployeeService::class)->update($existing, $changes);

            return;
        }

        // CREATE — honour a supplied Employee Code, else the service generates one.
        if ($code !== '') {
            $attrs['employee_code'] = $code;
        }
        $attrs['business_id'] = $businessId;
        $attrs['status'] = 'active';
        app(EmployeeService::class)->create($attrs);
    }

    private function resolve(string $modelClass, ?string $name): ?int
    {
        $name = trim((string) $name);
        if ($name === '') {
            return null;
        }

        return $modelClass::where('name', $name)->value('id');
    }
}
