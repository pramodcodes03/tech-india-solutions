<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetRepairRequest extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'request_code',
        'asset_id',
        'asset_type',
        'vendor_name',
        'repair_delivery_date',
        'description',
        'estimated_cost',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
        'approval_remarks',
        'costing_requested_amount',
        'costing_description',
        'costing_status',
        'costing_approved_by',
        'costing_approved_at',
        'costing_remarks',
    ];

    protected function casts(): array
    {
        return [
            'repair_delivery_date' => 'date',
            'approved_at'          => 'datetime',
            'costing_approved_at'  => 'datetime',
            'estimated_cost'       => 'decimal:2',
            'costing_requested_amount' => 'decimal:2',
        ];
    }

    // ── Status helpers ──────────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isCostPending(): bool
    {
        return $this->status === 'cost_approval_pending';
    }

    public function canRaiseCostApproval(): bool
    {
        return $this->isApproved() && $this->costing_status === null;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending'               => 'Pending',
            'approved'              => 'Approved',
            'rejected'              => 'Rejected',
            'cost_approval_pending' => 'Cost Approval Pending',
            'cost_approved'         => 'Cost Approved',
            'cost_rejected'         => 'Cost Rejected',
            default                 => ucfirst($this->status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending'               => 'warning',
            'approved'              => 'success',
            'rejected'              => 'danger',
            'cost_approval_pending' => 'info',
            'cost_approved'         => 'success',
            'cost_rejected'         => 'danger',
            default                 => 'secondary',
        };
    }

    // ── Relationships ───────────────────────────────────────────────────

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class)->withTrashed();
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by');
    }

    public function costingApprover(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'costing_approved_by');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(AssetRepairActivityLog::class)->orderByDesc('performed_at');
    }
}
