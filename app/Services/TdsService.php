<?php

namespace App\Services;

use App\Models\TdsSlab;
use App\Support\HrSettings;

/**
 * Slab-based income-tax (TDS) engine. Annualises taxable income, applies the
 * configured progressive slabs, and returns the monthly TDS. Falls back to a
 * standard set of slabs if a business hasn't configured its own.
 */
class TdsService
{
    /** Default new-regime style slabs (suggested defaults; admin-editable). */
    public const DEFAULT_SLABS = [
        ['lower' => 0,        'upper' => 300000,  'rate' => 0],
        ['lower' => 300000,   'upper' => 700000,  'rate' => 5],
        ['lower' => 700000,   'upper' => 1000000, 'rate' => 10],
        ['lower' => 1000000,  'upper' => 1200000, 'rate' => 15],
        ['lower' => 1200000,  'upper' => 1500000, 'rate' => 20],
        ['lower' => 1500000,  'upper' => null,    'rate' => 30],
    ];

    public function currentFinancialYear(): string
    {
        $now = now();
        $startYear = $now->month >= 4 ? $now->year : $now->year - 1;

        return $startYear.'-'.($startYear + 1);
    }

    /**
     * @return array<int, array{lower:float, upper:?float, rate:float}>
     */
    public function slabs(int $businessId, ?string $fy = null): array
    {
        $fy = $fy ?: $this->currentFinancialYear();

        $rows = TdsSlab::where('business_id', $businessId)
            ->where('financial_year', $fy)
            ->orderBy('sort_order')->orderBy('lower')
            ->get();

        if ($rows->isEmpty()) {
            return array_map(fn ($s) => ['lower' => (float) $s['lower'], 'upper' => $s['upper'], 'rate' => (float) $s['rate']], self::DEFAULT_SLABS);
        }

        return $rows->map(fn ($r) => [
            'lower' => (float) $r->lower,
            'upper' => $r->upper !== null ? (float) $r->upper : null,
            'rate' => (float) $r->rate_percent,
        ])->all();
    }

    /**
     * Annual tax for a given annual taxable income, after the standard deduction.
     */
    public function annualTax(float $annualGross, int $businessId, ?string $fy = null): float
    {
        $standardDeduction = HrSettings::float('tds_standard_deduction', 50000);
        $taxable = max(0, $annualGross - $standardDeduction);

        $tax = 0.0;
        foreach ($this->slabs($businessId, $fy) as $slab) {
            $lower = $slab['lower'];
            $upper = $slab['upper'] ?? INF;
            if ($taxable > $lower) {
                $slice = min($taxable, $upper) - $lower;
                $tax += $slice * ($slab['rate'] / 100);
            }
        }

        // 4% health & education cess.
        $tax *= 1.04;

        return round($tax, 2);
    }

    public function monthlyTds(float $monthlyGross, int $businessId, ?string $fy = null): float
    {
        return round($this->annualTax($monthlyGross * 12, $businessId, $fy) / 12, 2);
    }
}
