<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use BelongsToBusiness;

    protected $table = 'attendance';

    /**
     * Every status a day can carry, with the label and the register short code
     * the client's Master Roll uses. One definition so the calendar, the
     * monthly summary and the printed register cannot drift apart.
     */
    public const STATUS_LABELS = [
        'present' => ['Present', 'P'],
        'half_day' => ['Half Day', 'HD'],
        'half_day_leave' => ['Half Day / Leave', 'HD/L'],
        'half_day_week_off' => ['Half Day / Week Off', 'HD/WO'],
        // Half the day sanctioned as leave, the other half never worked and
        // not an off-day. Derived for display only — never stored — because
        // it is the absence of a worked half that defines it.
        'half_day_leave_absent' => ['0.5 Leave / 0.5 Absent', 'L/A'],
        'leave_week_off' => ['Leave / Week Off', 'L/WO'],
        'on_leave' => ['Leave', 'L'],
        'week_off' => ['Week Off', 'WO'],
        'comp_off' => ['Comp Off', 'CO'],
        'holiday' => ['Holiday', 'H'],
        'absent' => ['Absent', 'A'],
        'future' => ['—', '—'],
    ];

    /** How much of a day each status counts as a week-off. */
    public const WEEK_OFF_WEIGHT = [
        'week_off' => 1.0,
        'half_day_week_off' => 0.5,
        'leave_week_off' => 0.5,
    ];

    /**
     * Statuses an admin may write by hand, as value => label.
     *
     * One list so the correction form, the register filter and the server-side
     * validation cannot drift — a dropdown offering a status the validator
     * rejects is the kind of thing that only shows up in production.
     * 'half_day_leave_absent' is absent by design: it is derived, never typed.
     */
    public const SELECTABLE_STATUSES = [
        'present', 'absent', 'half_day', 'half_day_week_off',
        'on_leave', 'leave_week_off', 'holiday', 'weekend',
    ];

    /** Statuses that count as a fully or partly paid day when set by hand. */
    public const MANUAL_PAID_WEIGHT = [
        'on_leave' => 1.0,
        'leave_week_off' => 1.0,
    ];

    public static function statusLabel(?string $status): string
    {
        return self::STATUS_LABELS[$status][0] ?? ucfirst(str_replace('_', ' ', (string) $status));
    }

    public static function statusCode(?string $status): string
    {
        return self::STATUS_LABELS[$status][1] ?? '—';
    }

    protected $fillable = [
        'business_id',
        'employee_id', 'date', 'check_in', 'check_out',
        'check_in_locked', 'check_out_locked',
        'hours_worked', 'status', 'half_day_portion', 'source', 'biometric_ref',
        'remarks', 'created_by',
        'shift', 'start_time',
        'late_hours', 'early_hours', 'over_time', 'break_minutes',
        'in_temp', 'out_temp', 'card_no',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'hours_worked' => 'decimal:2',
            'in_temp' => 'decimal:2',
            'out_temp' => 'decimal:2',
            'check_in_locked' => 'boolean',
            'check_out_locked' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
