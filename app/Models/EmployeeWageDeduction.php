<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A deduction made from wages — Form E, Punjab Shops and Commercial
 * Establishments Rules, 1958.
 *
 * Wider than a fine: Form E covers any deduction the employer makes and has to
 * record the fault, whether the employee was heard, what the money was used for
 * and when.
 */
class EmployeeWageDeduction extends Model
{
    use BelongsToBusiness, LogsActivity;

    protected $fillable = [
        'business_id', 'employee_id', 'wage_period', 'wages_payable',
        'deduction_amount', 'deduction_date', 'fault', 'showed_cause',
        'purpose', 'utilised_on', 'balance', 'remarks', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'deduction_date' => 'date',
            'utilised_on' => 'date',
            'showed_cause' => 'boolean',
            'wages_payable' => 'decimal:2',
            'deduction_amount' => 'decimal:2',
            'balance' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $event) => "Wage deduction was {$event}");
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
