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
        return ['First Name', 'Last Name', 'Email', 'Phone', 'Department', 'Designation', 'Joining Date', 'Employment Type'];
    }

    public function sampleRow(): array
    {
        return ['Asha', 'Verma', 'asha@example.com', '9876543210', 'Engineering', 'Software Engineer', '2026-06-01', 'full_time'];
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
        $email = trim($row['email'] ?? '');
        if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email.';
        }
        if ($email !== '' && Employee::where('email', $email)->exists()) {
            $errors[] = "Email {$email} already exists.";
        }
        $jd = trim($row['joining date'] ?? '');
        if ($jd !== '' && ! strtotime($jd)) {
            $errors[] = 'Invalid joining date.';
        }

        return $errors;
    }

    public function importRow(array $row, int $businessId): void
    {
        $deptId = $this->resolve(Department::class, $row['department'] ?? null);
        $desigId = $this->resolve(Designation::class, $row['designation'] ?? null);

        app(EmployeeService::class)->create([
            'business_id' => $businessId,
            'first_name' => trim($row['first name']),
            'last_name' => trim($row['last name'] ?? '') ?: null,
            'email' => trim($row['email'] ?? '') ?: null,
            'phone' => trim($row['phone'] ?? '') ?: null,
            'department_id' => $deptId,
            'designation_id' => $desigId,
            'joining_date' => ! empty($row['joining date']) ? date('Y-m-d', strtotime($row['joining date'])) : null,
            'employment_type' => $this->normalizeEmploymentType($row['employment type'] ?? null),
            'status' => 'active',
        ]);
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
