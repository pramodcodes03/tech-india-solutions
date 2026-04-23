<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class LeaveRequest extends Model
{
    use LogsActivity;

    protected $fillable = [
        'request_code', 'employee_id', 'leave_type_id',
        'from_date', 'to_date', 'days', 'paid_days', 'unpaid_days', 'day_portion',
        'reason', 'status', 'approver_id', 'actioned_at', 'approver_remarks',
    ];

    protected function casts(): array
    {
        return [
            'from_date' => 'date',
            'to_date' => 'date',
            'days' => 'decimal:1',
            'paid_days' => 'decimal:1',
            'unpaid_days' => 'decimal:1',
            'actioned_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $event) => "Leave request was {$event}");
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approver_id');
    }
}
