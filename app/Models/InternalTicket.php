<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InternalTicket extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'ticket_number', 'employee_id', 'department', 'category_id',
        'subject', 'description', 'priority', 'status', 'assigned_to',
        'tat_due_at', 'tat_breached', 'escalation_level', 'escalated_at',
        'resolved_at', 'closed_at', 'source', 'related_type', 'related_id',
    ];

    protected function casts(): array
    {
        return [
            'tat_due_at' => 'datetime',
            'tat_breached' => 'boolean',
            'escalated_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public const DEPARTMENTS = ['hr' => 'HR', 'it' => 'IT', 'admin' => 'Admin', 'accounts' => 'Accounts'];

    public const STATUSES = [
        'open' => 'Open', 'assigned' => 'Assigned', 'in_review' => 'In Review',
        'resolved' => 'Resolved', 'closed' => 'Closed',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(InternalTicketCategory::class, 'category_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_to');
    }

    /** Alias used by the notification RecipientResolver (ticket.assignee). */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_to');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(InternalTicketComment::class)->oldest();
    }

    public function getDepartmentLabelAttribute(): string
    {
        return self::DEPARTMENTS[$this->department] ?? ucfirst($this->department);
    }

    public function isOpenState(): bool
    {
        return ! in_array($this->status, ['resolved', 'closed']);
    }

    public function isBreaching(): bool
    {
        return $this->isOpenState() && $this->tat_due_at && $this->tat_due_at->isPast();
    }
}
