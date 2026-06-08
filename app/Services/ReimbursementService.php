<?php

namespace App\Services;

use App\Models\ReimbursementClaim;
use App\Models\ReimbursementClaimLog;
use App\Notifications\NotificationDispatcher;
use Illuminate\Support\Facades\Auth;

class ReimbursementService
{
    public function nextCode(int $businessId): string
    {
        $count = ReimbursementClaim::where('business_id', $businessId)->count();

        return 'RMB-'.str_pad((string) ($count + 1), 5, '0', STR_PAD_LEFT);
    }

    public function create(array $data): ReimbursementClaim
    {
        $data['claim_code'] = $this->nextCode($data['business_id']);
        $data['status'] = 'submitted';
        $claim = ReimbursementClaim::create($data);

        $this->log($claim, 'submitted', 'Claim submitted', 'employee');
        NotificationDispatcher::fire('reimbursement.submitted', $claim);

        return $claim;
    }

    /**
     * Move a claim along its lifecycle. $status in:
     * under_review, approved, rejected, disbursed.
     */
    public function transition(ReimbursementClaim $claim, string $status, array $extra = []): ReimbursementClaim
    {
        $claim->status = $status;
        $claim->reviewed_by = Auth::guard('admin')->id();
        $claim->reviewed_at = now();

        if (array_key_exists('remarks', $extra)) {
            $claim->review_remarks = $extra['remarks'];
        }
        if ($status === 'approved' && isset($extra['approved_amount'])) {
            $claim->approved_amount = $extra['approved_amount'];
        }
        if ($status === 'disbursed') {
            $claim->disbursed_at = now();
            $claim->payment_reference = $extra['payment_reference'] ?? $claim->payment_reference;
        }
        $claim->save();

        $this->log($claim, $status, $extra['remarks'] ?? null, 'admin');

        $event = match ($status) {
            'approved' => 'reimbursement.approved',
            'rejected' => 'reimbursement.rejected',
            'disbursed' => 'reimbursement.disbursed',
            default => null,
        };
        if ($event) {
            NotificationDispatcher::fire($event, $claim);
        }

        return $claim;
    }

    private function log(ReimbursementClaim $claim, string $status, ?string $remarks, string $by): void
    {
        ReimbursementClaimLog::create([
            'business_id' => $claim->business_id,
            'reimbursement_claim_id' => $claim->id,
            'status' => $status,
            'remarks' => $remarks,
            'admin_id' => $by === 'admin' ? Auth::guard('admin')->id() : null,
            'employee_id' => $by === 'employee' ? $claim->employee_id : null,
        ]);
    }
}
