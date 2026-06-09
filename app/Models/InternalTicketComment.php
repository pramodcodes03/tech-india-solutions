<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternalTicketComment extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'internal_ticket_id', 'body',
        'author_admin_id', 'author_employee_id', 'is_internal_note',
    ];

    protected function casts(): array
    {
        return ['is_internal_note' => 'boolean'];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(InternalTicket::class, 'internal_ticket_id');
    }

    public function authorAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'author_admin_id');
    }

    public function authorEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'author_employee_id');
    }

    public function getAuthorNameAttribute(): string
    {
        return $this->authorAdmin?->name ?? $this->authorEmployee?->full_name ?? 'System';
    }
}
