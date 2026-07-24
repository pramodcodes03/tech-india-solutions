<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Requisition extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'requisition_code', 'requested_by', 'category',
        'title', 'purpose', 'requested_amount', 'estimated_amount',
        'status', 'current_level', 'disbursed_at', 'payment_reference', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'requested_amount' => 'decimal:2',
            'estimated_amount' => 'decimal:2',
            'disbursed_at' => 'datetime',
        ];
    }

    public const CATEGORIES = [
        'furniture' => 'Furniture',
        'chairs' => 'Chairs',
        'systems' => 'Systems',
        'it_equipment' => 'IT Equipment',
        'stationery' => 'Stationery',
        'other' => 'Other',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'requested_by');
    }

    /** Alias used by the notification RecipientResolver (admin.creator). */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'requested_by');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(RequisitionApproval::class)->orderBy('level');
    }

    public function getCategoryLabelAttribute(): string
    {
        // Prefer the manageable lookup table (covers custom categories);
        // fall back to the legacy constant, then a humanised slug.
        $label = RequisitionCategory::withTrashed()
            ->where('business_id', $this->business_id)
            ->where('key', $this->category)
            ->value('label');

        return $label ?? self::CATEGORIES[$this->category] ?? ucfirst((string) str_replace('_', ' ', $this->category));
    }

    public function currentApproval(): ?RequisitionApproval
    {
        return $this->approvals()->where('level', $this->current_level)->first();
    }
}
