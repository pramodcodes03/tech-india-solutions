<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * One day's overtime for one employee — Form IV, Punjab Minimum Wages
 * Rules, 1950.
 *
 * Kept as a day-level row rather than a monthly total because the form prints
 * "dates on which over-time worked" alongside the extent worked on each
 * occasion, and both have to reconcile to the month's overtime earnings.
 */
class EmployeeOvertime extends Model
{
    use BelongsToBusiness, LogsActivity;

    protected $fillable = [
        'business_id', 'employee_id', 'worked_on', 'hours',
        'normal_rate', 'overtime_rate', 'normal_earnings', 'overtime_earnings',
        'paid_on', 'production_note', 'remarks', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'worked_on' => 'date',
            'paid_on' => 'date',
            'hours' => 'decimal:2',
            'normal_rate' => 'decimal:2',
            'overtime_rate' => 'decimal:2',
            'normal_earnings' => 'decimal:2',
            'overtime_earnings' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $event) => "Overtime record was {$event}");
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }
}
