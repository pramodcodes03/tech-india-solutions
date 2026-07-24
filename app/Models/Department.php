<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Department extends Model
{
    use BelongsToBusiness, LogsActivity, SoftDeletes;

    protected $fillable = [
        'business_id',
        'code', 'name', 'description', 'head_id', 'el_working_days_required', 'cl_sl_working_days', 'status',
        'created_by', 'updated_by', 'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'el_working_days_required' => 'integer',
            'cl_sl_working_days' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $event) => "Department was {$event}");
    }

    public function head(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'head_id');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function designations(): HasMany
    {
        return $this->hasMany(Designation::class);
    }

    public function feedback(): HasMany
    {
        return $this->hasMany(DepartmentFeedback::class);
    }
}
