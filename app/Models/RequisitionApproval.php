<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequisitionApproval extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'requisition_id', 'level', 'approver_role',
        'approver_id', 'status', 'remarks', 'actioned_at',
    ];

    protected function casts(): array
    {
        return ['actioned_at' => 'datetime'];
    }

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(Requisition::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approver_id');
    }
}
