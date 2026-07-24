<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\PayrollAdjustment;
use App\Models\SalaryStructure;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Automatic arrears computation. When a salary revision's effective_from is
 * backdated (mid-month or to a past month), the employee is owed the
 * difference between the new and the previous monthly gross for every elapsed
 * month since the revision took effect. This computes that and books it as an
 * arrears payroll adjustment for the target month.
 */
class ArrearsService
{
    /**
     * Compute arrears owed for an employee's current (revised) structure.
     *
     * @return array{applicable:bool, months:int, monthly_diff:float, arrears:float, from:?string, reason:string}
     */
    public function compute(Employee $employee, ?Carbon $asOf = null): array
    {
        $asOf = $asOf ?? now();

        $current = SalaryStructure::where('employee_id', $employee->id)
            ->where('is_current', true)->first();

        if (! $current) {
            return $this->none('No current salary structure.');
        }

        $effectiveFrom = $current->effective_from ? Carbon::parse($current->effective_from) : null;
        if (! $effectiveFrom || $effectiveFrom->gte($asOf->copy()->startOfMonth())) {
            return $this->none('Revision is not backdated — no arrears.');
        }

        // Previous structure = the most recent one that ended before/at the
        // new structure's effective date.
        $previous = SalaryStructure::where('employee_id', $employee->id)
            ->where('id', '!=', $current->id)
            ->orderByDesc('effective_from')
            ->first();

        $prevGross = (float) ($previous->gross_monthly ?? 0);
        $newGross = (float) $current->gross_monthly;
        $monthlyDiff = round($newGross - $prevGross, 2);

        if ($monthlyDiff <= 0) {
            return $this->none('New gross is not higher than the previous — no arrears.');
        }

        // Full elapsed months from effective_from up to (but excluding) the
        // current month — those are the months already paid at the old rate.
        $months = $effectiveFrom->copy()->startOfMonth()->diffInMonths($asOf->copy()->startOfMonth());
        if ($months < 1) {
            return $this->none('Less than a full month elapsed since revision.');
        }

        $arrears = round($monthlyDiff * $months, 2);

        return [
            'applicable' => true,
            'months' => $months,
            'monthly_diff' => $monthlyDiff,
            'arrears' => $arrears,
            'from' => $effectiveFrom->toDateString(),
            'reason' => "Arrears for {$months} month(s) @ {$monthlyDiff}/mo since {$effectiveFrom->format('d M Y')}",
        ];
    }

    /**
     * Compute and book the arrears as an adjustment for the given month/year.
     */
    public function generate(Employee $employee, int $month, int $year): array
    {
        $result = $this->compute($employee);
        if (! $result['applicable']) {
            return $result;
        }

        PayrollAdjustment::create([
            'business_id' => $employee->business_id,
            'employee_id' => $employee->id,
            'month' => $month,
            'year' => $year,
            'component' => 'arrears',
            'amount' => $result['arrears'],
            'note' => $result['reason'],
            'created_by' => Auth::guard('admin')->id(),
        ]);

        return $result;
    }

    private function none(string $reason): array
    {
        return ['applicable' => false, 'months' => 0, 'monthly_diff' => 0, 'arrears' => 0, 'from' => null, 'reason' => $reason];
    }
}
