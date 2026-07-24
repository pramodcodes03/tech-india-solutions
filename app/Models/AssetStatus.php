<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class AssetStatus extends Model
{
    use BelongsToBusiness, LogsActivity, SoftDeletes;

    protected $fillable = [
        'business_id', 'key', 'label', 'color',
        'sort_order', 'is_active', 'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_system' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $event) => "Asset status was {$event}");
    }

    /**
     * Default colour map for the system-seeded statuses + the new ones
     * introduced in this migration. Used by views that render a badge:
     * "color" is a Tailwind variant fragment (success/danger/warning/info/etc).
     */
    public static function defaultPalette(): array
    {
        return [
            // Original
            'draft'                 => 'secondary',
            'in_storage'            => 'info',
            'assigned'              => 'success',
            'in_maintenance'        => 'warning',
            'retired'               => 'danger',
            'disposed'              => 'danger',
            // New
            'backup_system'         => 'info',
            'discard_assets'        => 'warning',
            'discarded'             => 'danger',
            'faulty_under_repair'   => 'warning',
            'non_repairable'        => 'danger',
            'pending'               => 'secondary',
            'sent_for_repair'       => 'warning',
            'sold'                  => 'secondary',
            'sold_out_to_advisors'  => 'secondary',
        ];
    }
}
