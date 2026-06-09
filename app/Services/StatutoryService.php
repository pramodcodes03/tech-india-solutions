<?php

namespace App\Services;

use App\Models\Payslip;
use App\Support\HrSettings;

/**
 * Statutory compliance computations — EPF/EPS split, ESI employer share, PT,
 * and Labour Welfare Fund — derived from generated payslips. Powers the
 * monthly compliance registers, PF/ESI challans and the bank-transfer export.
 */
class StatutoryService
{
    /** EPS is 8.33% of wages capped at the PF wage ceiling. */
    public const EPS_RATE = 8.33;

    /**
     * Build the per-employee statutory register for a payroll month.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function register(int $month, int $year)
    {
        $pfWageCap = HrSettings::float('pf_wage_cap', 15000);
        $lwfEmp = HrSettings::float('lwf_employee', 0);
        $lwfEr = HrSettings::float('lwf_employer', 0);
        $lwfMonths = $this->lwfMonths();
        $lwfApplies = in_array($month, $lwfMonths, true);

        return Payslip::with(['employee'])
            ->where('month', $month)->where('year', $year)
            ->get()
            ->map(function (Payslip $p) use ($pfWageCap, $lwfEmp, $lwfEr, $lwfApplies) {
                $basic = (float) $p->basic;

                // Employee PF (already on the payslip as `pf`).
                $pfEmployee = (float) $p->pf;

                // EPS = 8.33% of min(basic, cap); employer EPF = employer 12% − EPS.
                $epsWage = min($basic, $pfWageCap);
                $eps = round($epsWage * (self::EPS_RATE / 100), 2);
                $employerPfTotal = $pfEmployee; // employer matches 12% of basic
                $employerEpf = round(max(0, $employerPfTotal - $eps), 2);

                // ESI employer share = 3.25% of gross (when ESI applies / employee had ESI).
                $esiEmployee = (float) $p->esi;
                $esiEmployer = $esiEmployee > 0 ? round((float) $p->gross_earnings * 0.0325, 2) : 0.0;

                return [
                    'employee' => $p->employee,
                    'employee_name' => $p->employee?->full_name,
                    'employee_code' => $p->employee?->employee_code,
                    'uan' => $p->employee?->uan_number,
                    'esi_number' => $p->employee?->esi_number,
                    'gross' => (float) $p->gross_earnings,
                    'basic' => $basic,
                    'pf_employee' => $pfEmployee,
                    'pf_employer_epf' => $employerEpf,
                    'eps' => $eps,
                    'esi_employee' => $esiEmployee,
                    'esi_employer' => $esiEmployer,
                    'pt' => (float) $p->professional_tax,
                    'tds' => (float) $p->tds,
                    'lwf_employee' => $lwfApplies ? $lwfEmp : 0.0,
                    'lwf_employer' => $lwfApplies ? $lwfEr : 0.0,
                    'net_pay' => (float) $p->net_pay,
                ];
            });
    }

    /**
     * Months in which LWF is deducted, based on the configured frequency.
     *
     * @return int[]
     */
    public function lwfMonths(): array
    {
        return match (HrSettings::get('lwf_frequency', 'half_yearly')) {
            'monthly' => range(1, 12),
            'annual' => [12],
            default => [6, 12], // half-yearly: June & December
        };
    }

    public function totals(int $month, int $year): array
    {
        $rows = $this->register($month, $year);

        return [
            'pf_employee' => round($rows->sum('pf_employee'), 2),
            'pf_employer_epf' => round($rows->sum('pf_employer_epf'), 2),
            'eps' => round($rows->sum('eps'), 2),
            'esi_employee' => round($rows->sum('esi_employee'), 2),
            'esi_employer' => round($rows->sum('esi_employer'), 2),
            'pt' => round($rows->sum('pt'), 2),
            'tds' => round($rows->sum('tds'), 2),
            'lwf_employee' => round($rows->sum('lwf_employee'), 2),
            'lwf_employer' => round($rows->sum('lwf_employer'), 2),
        ];
    }
}
