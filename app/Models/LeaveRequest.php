<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class LeaveRequest extends Model
{
    use BelongsToBusiness, LogsActivity;

    protected $fillable = [
        'business_id',
        'request_code', 'employee_id', 'leave_type_id', 'is_combined',
        'from_date', 'to_date', 'days', 'paid_days', 'unpaid_days', 'day_portion',
        'reason', 'status', 'approver_id', 'approver_employee_id', 'actioned_at', 'approver_remarks',
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
            'is_combined' => 'boolean',
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

    /**
     * Contributing leave types for a Combined Leave request.
     *
     * Empty for an ordinary single-type request — read `is_combined` rather
     * than counting these, so a request is never mistaken for combined just
     * because the relation happens to be loaded.
     */
    public function splits(): HasMany
    {
        return $this->hasMany(LeaveRequestSplit::class);
    }

    /**
     * "0.5 Casual Leave + 0.5 Sick Leave" — the one-line description of how a
     * combined request is funded, used on the request, the leave card and the
     * approval screen.
     */
    public function getSplitLabelAttribute(): ?string
    {
        if (! $this->is_combined) {
            return null;
        }

        return $this->splits
            ->map(fn (LeaveRequestSplit $s) => rtrim(rtrim(number_format((float) $s->days, 1, '.', ''), '0'), '.')
                .' '.($s->leaveType?->name ?? 'Leave'))
            ->implode(' + ');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approver_id');
    }

    /** Manager (Department Head) who actioned this from the employee portal. */
    public function approverEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approver_employee_id');
    }

    /**
     * Display name of whoever actioned the request — an Admin/HR or a
     * Department Head manager — whichever path was used.
     */
    public function getApproverNameAttribute(): ?string
    {
        if ($this->approver_id && $this->approver) {
            // Masked so a Super Admin's approval reads as "System Admin".
            return $this->approver->display_name;
        }
        if ($this->approver_employee_id && $this->approverEmployee) {
            return $this->approverEmployee->full_name.' (Manager)';
        }

        return null;
    }
}
