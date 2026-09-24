<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The escalation trail: who acted at each stage, when, and what they said.
 *
 * Written by PerformanceWorkflowService on every transition, so a finalised
 * review can always be explained after the fact.
 */
class PerformanceHistory extends Model
{
    use BelongsToBusiness;

    protected $table = 'performance_histories';

    protected $fillable = [
        'business_id', 'performance_cycle_id', 'employee_id',
        'stage', 'action', 'actor_admin_id', 'actor_employee_id', 'remarks',
    ];

    public function actorAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'actor_admin_id');
    }

    public function actorEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'actor_employee_id');
    }

    public function getActorNameAttribute(): string
    {
        return $this->actorEmployee?->full_name
            ?? $this->actorAdmin?->display_name
            ?? 'System';
    }

    public function getStageLabelAttribute(): string
    {
        return match ($this->stage) {
            'self' => 'Employee',
            'manager' => 'Manager',
            'hr' => 'HR',
            'admin' => 'Admin',
            default => ucfirst((string) $this->stage),
        };
    }
}
