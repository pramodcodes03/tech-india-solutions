<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketEscalationLevel extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'department', 'level', 'after_days', 'owner_admin_id', 'status',
    ];

    protected function casts(): array
    {
        return ['status' => 'boolean'];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'owner_admin_id');
    }
}
