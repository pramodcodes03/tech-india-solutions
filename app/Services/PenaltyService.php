<?php

namespace App\Services;

use App\Models\Penalty;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class PenaltyService
{
    public function generateCode(): string
    {
        $prefix = 'PEN-'.date('Ym').'-';
        $last = Penalty::where('penalty_code', 'like', $prefix.'%')
            ->orderByDesc('penalty_code')->first();
        $next = $last ? (int) substr($last->penalty_code, strlen($prefix)) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function create(array $data): Penalty
    {
        $data['penalty_code'] = $this->generateCode();
        $data['issued_by'] = Auth::guard('admin')->id();
        $data['original_amount'] = $data['amount'];
        $data['eligible_reduction_after'] = Carbon::parse($data['incident_date'])->addMonths(5)->toDateString();
        $data['status'] = $data['status'] ?? 'pending';

        return Penalty::create($data);
    }

    /**
     * Reduce or waive a penalty. Only allowed if:
     *   - status is still 'pending' (not yet deducted), AND
     *   - current date >= eligible_reduction_after (the PIP window has passed)
     */
    public function reduce(Penalty $penalty, float $newAmount, ?string $reason = null): Penalty
    {
        if ($penalty->status !== 'pending') {
            throw new \RuntimeException('Only pending penalties can be reduced.');
        }

        if ($penalty->eligible_reduction_after && Carbon::now()->lt(Carbon::parse($penalty->eligible_reduction_after))) {
            throw new \RuntimeException(
                'Penalty is not yet eligible for reduction. Eligible after '
                .Carbon::parse($penalty->eligible_reduction_after)->format('d M Y').'.'
            );
        }

        $newAmount = max(0, round($newAmount, 2));
        $status = $newAmount <= 0 ? 'waived' : 'reduced';

        $penalty->update([
            'amount' => $newAmount,
            'reduced_amount' => $newAmount,
            'reduced_on' => now()->toDateString(),
            'reduced_by' => Auth::guard('admin')->id(),
            'reduction_reason' => $reason,
            'status' => $status,
        ]);

        return $penalty->refresh();
    }
}
