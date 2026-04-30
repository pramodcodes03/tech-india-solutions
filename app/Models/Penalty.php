<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Penalty extends Model
{
    use BelongsToBusiness, LogsActivity;

    protected $fillable = [
        'business_id',
        'penalty_code', 'employee_id', 'penalty_type_id',
        'amount', 'incident_date', 'remarks', 'status',
        'original_amount', 'eligible_reduction_after',
        'reduced_amount', 'reduced_on', 'reduced_by', 'reduction_reason',
        'payslip_id', 'issued_by',
    ];

    protected function casts(): array
    {
        return [
            'incident_date' => 'date',
            'eligible_reduction_after' => 'date',
            'reduced_on' => 'date',
            'amount' => 'decimal:2',
            'original_amount' => 'decimal:2',
            'reduced_amount' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $event) => "Penalty was {$event}");
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function penaltyType(): BelongsTo
    {
        return $this->belongsTo(PenaltyType::class);
    }

    public function payslip(): BelongsTo
    {
        return $this->belongsTo(Payslip::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'issued_by');
    }
}
