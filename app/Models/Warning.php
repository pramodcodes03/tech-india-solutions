<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Warning extends Model
{
    use BelongsToBusiness, LogsActivity;

    /**
     * Escalation ladder — single source of truth for every dropdown, badge,
     * mail and report. Keys are stored in warnings.level; the 2026-08 shift
     * migration renumbered the original 1/2/3 to 2/3/4 to make room for PIP
     * (mildest, first) and ZTP (most severe, last).
     */
    public const LEVELS = [
        1 => 'PIP — Performance Improvement Plan',
        2 => 'Level 1 — HR Warning',
        3 => 'Level 2 — Manager Warning',
        4 => 'Level 3 — Director / Final Warning',
        5 => 'Level 4 — ZTP · Zero Tolerance Policy / Termination',
    ];

    /**
     * ZTP is the only rung with an employment-status consequence: the employee
     * is terminated outright. Every other rung — PIP through Director / Final
     * Warning — records the warning and nothing else.
     */
    public const TERMINATION_LEVELS = [5];

    protected $fillable = [
        'business_id',
        'warning_code', 'employee_id', 'level',
        'title', 'reason', 'action_required',
        'issued_on', 'status', 'acknowledged_at',
        'employee_response', 'issued_by',
    ];

    protected function casts(): array
    {
        return [
            'issued_on' => 'date',
            'acknowledged_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $event) => "Warning was {$event}");
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'issued_by');
    }

    public function getLevelLabelAttribute(): string
    {
        return self::LEVELS[(int) $this->level] ?? "Level {$this->level}";
    }
}
