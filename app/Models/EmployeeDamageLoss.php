<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A deduction for damage or loss caused by the neglect or default of an
 * employee — Form II, Punjab Minimum Wages Rules, 1950.
 */
class EmployeeDamageLoss extends Model
{
    use BelongsToBusiness, LogsActivity;

    protected $fillable = [
        'business_id', 'employee_id', 'damage_date', 'damage_description',
        'showed_cause', 'cause_particulars', 'deduction_date', 'deduction_amount',
        'instalments', 'realised_on', 'remarks', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'damage_date' => 'date',
            'deduction_date' => 'date',
            'realised_on' => 'date',
            'showed_cause' => 'boolean',
            'deduction_amount' => 'decimal:2',
            'instalments' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $event) => "Damage or loss deduction was {$event}");
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
