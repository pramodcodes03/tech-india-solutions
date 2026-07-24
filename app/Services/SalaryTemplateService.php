<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\SalaryStructure;
use App\Models\SalaryTemplate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SalaryTemplateService
{
    /**
     * Apply a template to a set of employees: end-date any current structure
     * and create a fresh approved structure from the template amounts.
     *
     * @return array{assigned:int, skipped:int}
     */
    public function assignToEmployees(SalaryTemplate $template, array $employeeIds, string $effectiveFrom): array
    {
        $assigned = 0;
        $skipped = 0;
        $adminId = Auth::guard('admin')->id();

        $employees = Employee::whereIn('id', $employeeIds)->get();

        foreach ($employees as $emp) {
            DB::transaction(function () use ($emp, $template, $effectiveFrom, $adminId, &$assigned) {
                // Close out the current structure.
                $emp->salaryStructures()->where('is_current', true)->update([
                    'is_current' => false,
                    'effective_to' => $effectiveFrom,
                ]);

                $gross = $template->gross_monthly;

                SalaryStructure::create([
                    'business_id' => $emp->business_id,
                    'employee_id' => $emp->id,
                    'effective_from' => $effectiveFrom,
                    'basic' => $template->basic,
                    'hra' => $template->hra,
                    'conveyance' => $template->conveyance,
                    'medical' => $template->medical,
                    'special' => $template->special,
                    'other_allowance' => $template->other_allowance,
                    'gross_monthly' => $gross,
                    'ctc_annual' => round($gross * 12, 2),
                    'pf_percent' => $template->pf_percent,
                    'esi_percent' => $template->esi_percent,
                    'professional_tax' => $template->professional_tax,
                    'monthly_tds' => 0,
                    'is_current' => true,
                    'status' => SalaryStructure::STATUS_APPROVED,
                    'notes' => "Applied from template: {$template->name}",
                    'created_by' => $adminId,
                    'reviewed_by' => $adminId,
                    'reviewed_at' => now(),
                ]);

                $assigned++;
            });
        }

        return ['assigned' => $assigned, 'skipped' => $skipped];
    }

    /**
     * Resolve the matching employee set for a template's scope.
     */
    public function candidateEmployees(SalaryTemplate $template)
    {
        return Employee::query()
            ->whereIn('status', ['active', 'probation', 'on_notice'])
            ->when($template->level === 'department' && $template->department_id,
                fn ($q) => $q->where('department_id', $template->department_id))
            ->when($template->level === 'category' && $template->employee_category,
                fn ($q) => $q->where('employment_type', $template->employee_category))
            ->orderBy('first_name')
            ->get();
    }
}
