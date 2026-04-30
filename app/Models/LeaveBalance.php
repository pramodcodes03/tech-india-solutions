<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'employee_id', 'leave_type_id', 'year',
        'allocated', 'used', 'pending', 'carried_forward',
    ];

    protected function casts(): array
    {
        return [
            'allocated' => 'decimal:2',
            'used' => 'decimal:2',
            'pending' => 'decimal:2',
            'carried_forward' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function getAvailableAttribute(): float
    {
        return (float) ($this->allocated + $this->carried_forward - $this->used - $this->pending);
    }
}
