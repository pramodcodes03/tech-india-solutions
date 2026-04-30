<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'name', 'start_time', 'end_time',
        'grace_minutes', 'half_day_after_minutes', 'status',
    ];

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
