<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class AssetMaintenanceLog extends Model
{
    use LogsActivity;

    protected $fillable = [
        'log_code', 'asset_id', 'type',
        'scheduled_date', 'performed_date',
        'performed_by', 'performed_by_employee_id', 'vendor_name',
        'parts_cost', 'labour_cost', 'total_cost', 'downtime_hours',
        'description', 'parts_used', 'resolution_notes', 'status',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'performed_date' => 'date',
            'parts_cost' => 'decimal:2',
            'labour_cost' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'downtime_hours' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $event) => "Maintenance log was {$event}");
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'performed_by_employee_id');
    }
}
