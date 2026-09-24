<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/** A fine imposed on an employee — Form I, Punjab Minimum Wages Rules, 1950. */
class EmployeeFine extends Model
{
    use BelongsToBusiness, LogsActivity;

    protected $fillable = [
        'business_id', 'employee_id', 'offence_date', 'offence_nature',
        'showed_cause', 'cause_particulars', 'wage_rate',
        'fine_date', 'fine_amount', 'realised_on', 'remarks', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'offence_date' => 'date',
            'fine_date' => 'date',
            'realised_on' => 'date',
            'showed_cause' => 'boolean',
            'wage_rate' => 'decimal:2',
            'fine_amount' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $event) => "Fine was {$event}");
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
