<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Form A, Punjab Child Labour (Prohibition and Regulation) Rules, 1997.
 *
 * The register must be produced whether or not anyone is on it; an establishment
 * with no children employed files it as a nil return, which is what an empty
 * table renders as. This is deliberately not tied to the employees table — the
 * form is a separate statutory record with its own fields.
 */
class ChildLabourRecord extends Model
{
    use BelongsToBusiness, LogsActivity;

    protected $fillable = [
        'business_id', 'child_name', 'father_name', 'date_of_birth',
        'permanent_address', 'joined_on', 'nature_of_work', 'daily_hours',
        'rest_intervals', 'wages_paid', 'left_on', 'remarks', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'joined_on' => 'date',
            'left_on' => 'date',
            'wages_paid' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $event) => "Child labour record was {$event}");
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }
}
