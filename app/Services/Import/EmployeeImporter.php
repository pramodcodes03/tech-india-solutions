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
        return ['Asha', 'Verma', 'asha@example.com', '9876543210', 'Engineering', 'Software Engineer', '2026-06-01', 'permanent'];
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
            'employment_type' => trim($row['employment type'] ?? '') ?: 'permanent',
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
