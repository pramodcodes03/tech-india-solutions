<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A single value in one of the trackers' dynamic dropdowns.
 *
 * @see database/migrations/2026_08_28_100001_create_tracker_options_table.php
 */
class TrackerOption extends Model
{
    use BelongsToBusiness, LogsActivity;

    public const TYPE_BREAK = 'break_type';

    public const TYPE_SOURCE = 'visitor_source';

    public const TYPE_PURPOSE = 'visit_purpose';

    /** type => label shown on the settings screen. */
    public const TYPES = [
        self::TYPE_BREAK => 'Break Type',
        self::TYPE_SOURCE => 'Visitor Source',
        self::TYPE_PURPOSE => 'Purpose of Visit',
    ];

    protected $fillable = ['business_id', 'type', 'name', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $event) => "Tracker option was {$event}");
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Ordered, active values for one dropdown — what every tracker form binds to. */
    public static function listFor(string $type): Collection
    {
        return static::ofType($type)->active()
            ->orderBy('sort_order')->orderBy('name')
            ->get();
    }
}
