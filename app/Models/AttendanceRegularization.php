<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRegularization extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'employee_id', 'attendance_id', 'date',
        'request_type', 'expected_in', 'expected_out', 'reason',
        'status', 'reviewed_by', 'reviewed_at', 'review_remarks',
        'sla_due_at', 'escalated', 'applied',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'reviewed_at' => 'datetime',
            'sla_due_at' => 'datetime',
            'escalated' => 'boolean',
            'applied' => 'boolean',
        ];
    }

    public const TYPES = [
        'missed_punch' => 'Missed Punch',
        'wrong_punch' => 'Wrong Punch Time',
        'forgot_checkout' => 'Forgot Check-out',
        'missed_day' => 'Whole Day Missing',
        'other' => 'Other',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'reviewed_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->request_type] ?? ucfirst($this->request_type);
    }

    /**
     * expected_in / expected_out are TIME columns (plain "HH:MM:SS" strings),
     * not cast to Carbon — so calling ->format() on them directly throws.
     * These accessors safely return a display-ready "HH:MM" or null.
     */
    public function getExpectedInTimeAttribute(): ?string
    {
        return $this->formatTime($this->expected_in);
    }

    public function getExpectedOutTimeAttribute(): ?string
    {
        return $this->formatTime($this->expected_out);
    }

    private function formatTime($value): ?string
    {
        if (empty($value)) {
            return null;
        }
        try {
            return \Carbon\Carbon::parse($value)->format('H:i');
        } catch (\Throwable) {
            return is_string($value) ? substr($value, 0, 5) : null;
        }
    }

    /** Open past the SLA window without resolution. */
    public function isBreaching(): bool
    {
        return $this->status === 'pending'
            && $this->sla_due_at
            && $this->sla_due_at->isPast();
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
