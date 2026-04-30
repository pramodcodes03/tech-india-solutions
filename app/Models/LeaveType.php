<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'code', 'name', 'annual_quota',
        'is_paid', 'carry_forward', 'max_carry_forward',
        'encashable', 'color', 'description', 'status',
    ];

    protected function casts(): array
    {
        return [
            'annual_quota' => 'decimal:1',
            'max_carry_forward' => 'decimal:1',
            'is_paid' => 'boolean',
            'carry_forward' => 'boolean',
            'encashable' => 'boolean',
        ];
    }

    public function balances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }
}
