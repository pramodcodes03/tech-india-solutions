<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompOffRequest extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'employee_id', 'worked_on', 'comp_date',
        'reason', 'status', 'approved_by', 'approved_by_employee_id', 'actioned_at', 'admin_remarks',
    ];

    protected function casts(): array
    {
        return [
            'worked_on'   => 'date',
            'comp_date'   => 'date',
            'actioned_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by');
    }

    /** The reporting manager (employee) who actioned this, if not an admin. */
    public function approverEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by_employee_id');
    }

    /** Display name of whoever actioned the request (admin or manager). */
    public function getApproverNameAttribute(): ?string
    {
        return $this->approver?->name
            ?? ($this->approverEmployee ? trim($this->approverEmployee->first_name.' '.$this->approverEmployee->last_name) : null);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
}
