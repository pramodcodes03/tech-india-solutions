<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/** An advance paid to an employee — Form II-A, Punjab Minimum Wages Rules, 1950. */
class EmployeeAdvance extends Model
{
    use BelongsToBusiness, LogsActivity;

    protected $fillable = [
        'business_id', 'employee_id', 'advance_date', 'advance_amount', 'purpose',
        'instalments', 'postponement_grounds', 'repaid_on', 'remarks', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'advance_date' => 'date',
            'repaid_on' => 'date',
            'advance_amount' => 'decimal:2',
            'instalments' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $event) => "Advance was {$event}");
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
