<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Payslip;
use App\Models\Penalty;
use App\Models\SalaryStructure;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    public function __construct(protected AttendanceService $attendance) {}

    /**
     * Build / preview a salary structure from CTC.
     * Standard India break-up (tweakable at create time):
     * Basic     = 50% of CTC/12
     * HRA       = 40% of Basic (metro) — using 40%
     * Conveyance = flat 1,600/mo
     * Medical   = flat 1,250/mo
     * Special   = remainder to balance gross
     *
     * Employer PF (12% of Basic) reduces gross-to-take-home indirectly
     * — here we store monthly gross and annual CTC; statutory employee-side
     * deductions are computed per-payslip.
     */
    public function buildStructureFromCtc(float $ctcAnnual): array
    {
        $monthly = round($ctcAnnual / 12, 2);
        $basic = round($monthly * 0.5, 2);
        $hra = round($basic * 0.4, 2);
        $conveyance = 1600.00;
        $medical = 1250.00;
        $special = round($monthly - $basic - $hra - $conveyance - $medical, 2);
        if ($special < 0) {
            $special = 0;
        }

        return [
            'basic' => $basic,
            'hra' => $hra,
            'conveyance' => $conveyance,
            'medical' => $medical,
            'special' => $special,
            'other_allowance' => 0,
            'gross_monthly' => round($basic + $hra + $conveyance + $medical + $special, 2),
            'ctc_annual' => $ctcAnnual,
        ];
    }

    public function saveStructure(Employee $employee, array $data): SalaryStructure
    {
        return DB::transaction(function () use ($employee, $data) {
            SalaryStructure::where('employee_id', $employee->id)
                ->where('is_current', true)
                ->update(['is_current' => false, 'effective_to' => Carbon::parse($data['effective_from'])->subDay()]);

            $data['employee_id'] = $employee->id;
            $data['is_current'] = true;
            $data['created_by'] = Auth::guard('admin')->id();

            return SalaryStructure::create($data);
        });
    }

    public function generatePayslipCode(): string
    {
        $prefix = 'PS-'.date('Ym').'-';
        $last = Payslip::where('payslip_code', 'like', $prefix.'%')
            ->orderByDesc('payslip_code')->first();
        $next = $last ? (int) substr($last->payslip_code, strlen($prefix)) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate a payslip for an employee for a given month/year.
     * Uses current salary structure, monthly attendance, and pending penalties.
     */
    public function generate(Employee $employee, int $month, int $year): Payslip
    {
        return DB::transaction(function () use ($employee, $month, $year) {
            $structure = $employee->salaryStructures()
                ->where('is_current', true)
                ->first();

            if (! $structure) {
                throw new \RuntimeException("No current salary structure for {$employee->employee_code}.");
            }

            $summary = $this->attendance->monthlySummary($employee->id, $month, $year);
            $periodStart = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $periodEnd = $periodStart->copy()->endOfMonth();
            $daysInMonth = $periodStart->daysInMonth;
            $workingDays = max(1, $summary['working_days']);
            $paidDays = (float) $summary['paid_days'];
            $lopDays = (float) $summary['lop_days'];

            // Pro-rate earnings by paid_days / working_days
            $ratio = $workingDays > 0 ? min(1, $paidDays / $workingDays) : 1;

            $basic = round($structure->basic * $ratio, 2);
            $hra = round($structure->hra * $ratio, 2);
            $conveyance = round($structure->conveyance * $ratio, 2);
            $medical = round($structure->medical * $ratio, 2);
            $special = round($structure->special * $ratio, 2);
            $otherAllowance = round($structure->other_allowance * $ratio, 2);

            $grossEarnings = round($basic + $hra + $conveyance + $medical + $special + $otherAllowance, 2);

            // PF: 12% of Basic (employee share), capped at 15000 basic if desired — keeping simple
            $pf = round($basic * ($structure->pf_percent / 100), 2);

            // ESI: 0.75% of gross, applicable only if gross <= 21000
            $esi = $grossEarnings <= 21000
                ? round($grossEarnings * ($structure->esi_percent / 100), 2)
                : 0;

            $pt = (float) $structure->professional_tax;
            $tds = (float) $structure->monthly_tds;

            // LOP deduction = per-day basic × lop_days (convention)
            $perDay = $workingDays > 0 ? round($structure->gross_monthly / $workingDays, 2) : 0;
            $lopDeduction = round($perDay * $lopDays, 2);

            // Pending penalties for this employee (not yet deducted)
            $pendingPenalties = Penalty::where('employee_id', $employee->id)
                ->where('status', 'pending')
                ->whereNull('payslip_id')
                ->get();
            $penaltyDeduction = round($pendingPenalties->sum('amount'), 2);

            $totalDeductions = round($pf + $esi + $pt + $tds + $lopDeduction + $penaltyDeduction, 2);
            $netPay = round($grossEarnings - $totalDeductions, 2);

            $payslip = Payslip::updateOrCreate(
                ['employee_id' => $employee->id, 'month' => $month, 'year' => $year],
                [
                    'payslip_code' => $this->generatePayslipCode(),
                    'period_start' => $periodStart->toDateString(),
                    'period_end' => $periodEnd->toDateString(),
                    'working_days' => $workingDays,
                    'paid_days' => $paidDays,
                    'lop_days' => $lopDays,
                    'basic' => $basic,
                    'hra' => $hra,
                    'conveyance' => $conveyance,
                    'medical' => $medical,
                    'special' => $special,
                    'other_allowance' => $otherAllowance,
                    'bonus' => 0,
                    'gross_earnings' => $grossEarnings,
                    'pf' => $pf,
                    'esi' => $esi,
                    'professional_tax' => $pt,
                    'tds' => $tds,
                    'penalty_deduction' => $penaltyDeduction,
                    'lop_deduction' => $lopDeduction,
                    'other_deductions' => 0,
                    'total_deductions' => $totalDeductions,
                    'net_pay' => $netPay,
                    'status' => 'generated',
                    'generated_by' => Auth::guard('admin')->id(),
                ]
            );

            // Link and mark penalties as deducted
            foreach ($pendingPenalties as $p) {
                $p->update(['status' => 'deducted', 'payslip_id' => $payslip->id]);
            }

            return $payslip;
        });
    }

    public function generateBulk(int $month, int $year): array
    {
        $employees = Employee::whereIn('status', ['active', 'probation', 'on_notice'])
            ->whereHas('salaryStructures', fn ($q) => $q->where('is_current', true))
            ->get();

        $success = 0;
        $errors = [];

        foreach ($employees as $employee) {
            try {
                $this->generate($employee, $month, $year);
                $success++;
            } catch (\Throwable $e) {
                $errors[$employee->employee_code] = $e->getMessage();
            }
        }

        return ['success' => $success, 'errors' => $errors];
    }
}
