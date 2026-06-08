<?php

namespace App\Services;

use App\Models\Requisition;
use App\Models\RequisitionApproval;
use App\Notifications\NotificationDispatcher;
use App\Support\HrSettings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RequisitionService
{
    /**
     * Configurable approval chain — an ordered list of admin role names that
     * must approve, in sequence. Admin-editable from settings; defaults to a
     * single Admin level if unset.
     *
     * @return string[]
     */
    public function approvalChain(): array
    {
        $raw = HrSettings::get('requisition_approval_roles', 'Admin');
        $roles = array_values(array_filter(array_map('trim', explode(',', (string) $raw))));

        return $roles ?: ['Admin'];
    }

    public function nextCode(int $businessId): string
    {
        $count = Requisition::where('business_id', $businessId)->count();

        return 'REQ-'.str_pad((string) ($count + 1), 5, '0', STR_PAD_LEFT);
    }

    public function create(array $data): Requisition
    {
        return DB::transaction(function () use ($data) {
            $data['requisition_code'] = $this->nextCode($data['business_id']);
            $data['requested_by'] = Auth::guard('admin')->id();
            $data['status'] = 'pending';
            $data['current_level'] = 1;

            $req = Requisition::create($data);

            // Seed the approval chain rows.
            foreach ($this->approvalChain() as $i => $role) {
                RequisitionApproval::create([
                    'business_id' => $req->business_id,
                    'requisition_id' => $req->id,
                    'level' => $i + 1,
                    'approver_role' => $role,
                    'status' => 'pending',
                ]);
            }

            NotificationDispatcher::fire('requisition.submitted', $req);

            return $req;
        });
    }

    /**
     * Approve at the current level. Advances to the next level, or marks the
     * whole requisition approved when the chain is exhausted.
     */
    public function approve(Requisition $req, ?string $remarks = null): Requisition
    {
        return DB::transaction(function () use ($req, $remarks) {
            $current = $req->currentApproval();
            if (! $current || $req->status !== 'pending') {
                return $req;
            }

            $current->update([
                'status' => 'approved',
                'approver_id' => Auth::guard('admin')->id(),
                'remarks' => $remarks,
                'actioned_at' => now(),
            ]);

            $hasNext = $req->approvals()->where('level', '>', $req->current_level)->exists();
            if ($hasNext) {
                $req->update(['current_level' => $req->current_level + 1]);
            } else {
                $req->update(['status' => 'approved']);
                NotificationDispatcher::fire('requisition.approved', $req);
            }

            return $req->refresh();
        });
    }

    public function reject(Requisition $req, string $remarks): Requisition
    {
        $current = $req->currentApproval();
        if ($current) {
            $current->update([
                'status' => 'rejected',
                'approver_id' => Auth::guard('admin')->id(),
                'remarks' => $remarks,
                'actioned_at' => now(),
            ]);
        }
        $req->update(['status' => 'rejected']);
        NotificationDispatcher::fire('requisition.rejected', $req);

        return $req;
    }

    public function disburse(Requisition $req, ?string $paymentReference): Requisition
    {
        $req->update([
            'status' => 'disbursed',
            'disbursed_at' => now(),
            'payment_reference' => $paymentReference,
        ]);
        NotificationDispatcher::fire('requisition.disbursed', $req);

        return $req;
    }
}
