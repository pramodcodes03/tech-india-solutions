<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Warning extends Model
{
    use LogsActivity;

    protected $fillable = [
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
        return match ((int) $this->level) {
            1 => 'HR Warning (Level 1)',
            2 => 'Manager Warning (Level 2)',
            3 => 'Director Warning — Termination (Level 3)',
            default => "Level {$this->level}",
        };
    }
}
