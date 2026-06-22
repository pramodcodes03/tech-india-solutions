<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternalTicketCategory extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'department', 'name', 'assigned_role', 'assigned_admin_id', 'tat_hours', 'status',
    ];

    protected function casts(): array
    {
        return ['status' => 'boolean'];
    }

    /** The specific admin (of the chosen role) who receives this category's tickets. */
    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_admin_id');
    }
}
