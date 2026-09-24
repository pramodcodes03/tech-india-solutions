<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/** The monthly diesel allocation an entry's spend is measured against. */
class DieselBudget extends Model
{
    use BelongsToBusiness, LogsActivity;

    protected $fillable = ['business_id', 'period_month', 'amount', 'notes', 'created_by'];

    protected function casts(): array
    {
        return [
            'period_month' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $event) => "Diesel budget was {$event}");
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }
}
