<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class VisitorLog extends Model
{
    use BelongsToBusiness, LogsActivity;

    /** Did the visitor actually turn up. */
    public const STATUSES = [
        'available' => 'Available',
        'not_available' => 'Not Available',
        'rescheduled' => 'Rescheduled',
        'no_show' => 'No Show',
    ];

    /** What came of the visit — drives the interview conversion analytic. */
    public const OUTCOMES = [
        'pending' => 'Pending',
        'selected' => 'Selected',
        'rejected' => 'Rejected',
        'on_hold' => 'On Hold',
        'joined' => 'Joined',
    ];

    protected $fillable = [
        'business_id', 'visit_date', 'visitor_name', 'mobile', 'source_id', 'purpose_id',
        'arrival_time', 'called_by', 'interview_by', 'availability_status', 'outcome',
        'remarks', 'recorded_by',
    ];

    protected function casts(): array
    {
        return ['visit_date' => 'date'];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $event) => "Visitor log was {$event}");
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(TrackerOption::class, 'source_id');
    }

    public function purpose(): BelongsTo
    {
        return $this->belongsTo(TrackerOption::class, 'purpose_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'recorded_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->availability_status] ?? ucfirst((string) $this->availability_status);
    }

    public function getOutcomeLabelAttribute(): string
    {
        return self::OUTCOMES[$this->outcome] ?? ucfirst((string) $this->outcome);
    }
}
