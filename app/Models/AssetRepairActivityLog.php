<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetRepairActivityLog extends Model
{
    use BelongsToBusiness;

    public $timestamps = false;

    protected $fillable = [
        'business_id',
        'asset_repair_request_id',
        'performed_by',
        'performed_by_name',
        'event',
        'remarks',
        'status_snapshot',
        'costing_status_snapshot',
        'performed_at',
    ];

    protected function casts(): array
    {
        return [
            'performed_at' => 'datetime',
        ];
    }

    public function getEventLabelAttribute(): string
    {
        return match ($this->event) {
            'request_raised'       => 'Repair Request Raised',
            'approved'             => 'Request Approved',
            'rejected'             => 'Request Rejected',
            'cost_approval_raised' => 'Cost Approval Requested',
            'cost_approved'        => 'Cost Approved',
            'cost_rejected'        => 'Cost Rejected',
            'remarked'             => 'Remark Added',
            default                => ucwords(str_replace('_', ' ', $this->event)),
        };
    }

    public function getEventIconAttribute(): string
    {
        return match ($this->event) {
            'request_raised'       => '📋',
            'approved'             => '✅',
            'rejected'             => '❌',
            'cost_approval_raised' => '💰',
            'cost_approved'        => '✅',
            'cost_rejected'        => '❌',
            'remarked'             => '💬',
            default                => '📌',
        };
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'performed_by');
    }

    public function repairRequest(): BelongsTo
    {
        return $this->belongsTo(AssetRepairRequest::class, 'asset_repair_request_id');
    }
}
