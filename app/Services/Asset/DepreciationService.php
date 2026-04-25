<?php

namespace App\Services\Asset;

use App\Models\Asset;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DepreciationService
{
    /**
     * Compute the monthly depreciation amount for a single asset.
     */
    public function monthlyAmount(Asset $asset): float
    {
        if ($asset->depreciation_method !== 'straight_line') return 0;
        if ((int) $asset->useful_life_years <= 0) return 0;

        $depreciableBase = max(0, (float) $asset->purchase_cost - (float) $asset->salvage_value);

        return round($depreciableBase / ($asset->useful_life_years * 12), 2);
    }

    /**
     * Preview rows for a given month-end (no DB writes).
     */
    public function preview(Carbon $asOf): Collection
    {
        $assets = Asset::whereIn('status', ['in_storage', 'assigned', 'in_maintenance'])
            ->where('depreciation_method', '!=', 'none')
            ->where('useful_life_years', '>', 0)
            ->where(function ($q) use ($asOf) {
                $q->whereNull('depreciation_start_date')
                    ->orWhere('depreciation_start_date', '<=', $asOf);
            })
            ->where(function ($q) use ($asOf) {
                $q->whereNull('last_depreciation_posted_on')
                    ->orWhere('last_depreciation_posted_on', '<', $asOf->copy()->startOfMonth());
            })
            ->get();

        return $assets->map(function (Asset $a) use ($asOf) {
            $monthly = $this->monthlyAmount($a);
            $newAccum = (float) $a->accumulated_depreciation + $monthly;
            $maxAccum = max(0, (float) $a->purchase_cost - (float) $a->salvage_value);
            $newAccum = min($newAccum, $maxAccum);
            $newBookValue = max((float) $a->salvage_value, (float) $a->purchase_cost - $newAccum);

            return (object) [
                'asset_id'         => $a->id,
                'asset_code'       => $a->asset_code,
                'name'             => $a->name,
                'method'           => $a->depreciation_method,
                'monthly'          => round($monthly, 2),
                'before_accum'     => round((float) $a->accumulated_depreciation, 2),
                'after_accum'      => round($newAccum, 2),
                'before_book_value'=> round((float) $a->current_book_value, 2),
                'after_book_value' => round($newBookValue, 2),
                'as_of'            => $asOf->copy()->endOfMonth()->toDateString(),
            ];
        })->filter(fn ($r) => $r->monthly > 0);
    }

    /**
     * Post depreciation for the given month-end. Idempotent per asset/month.
     */
    public function postMonth(Carbon $asOf, ?int $adminId = null): array
    {
        $rows = $this->preview($asOf);
        $count = 0;
        $totalAmount = 0;

        DB::transaction(function () use ($rows, $asOf, $adminId, &$count, &$totalAmount) {
            foreach ($rows as $r) {
                Asset::where('id', $r->asset_id)->update([
                    'accumulated_depreciation'      => $r->after_accum,
                    'current_book_value'            => $r->after_book_value,
                    'last_depreciation_posted_on'   => $asOf->copy()->endOfMonth()->toDateString(),
                    'updated_by'                    => $adminId,
                ]);
                $count++;
                $totalAmount += $r->monthly;
            }
        });

        return [
            'posted_count' => $count,
            'total_amount' => round($totalAmount, 2),
            'as_of'        => $asOf->copy()->endOfMonth()->toDateString(),
        ];
    }
}
