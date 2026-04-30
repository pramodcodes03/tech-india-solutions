<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Lead extends Model
{
    use BelongsToBusiness, LogsActivity, SoftDeletes;

    /**
     * Canonical lead source list. Keys are stored in DB (lowercase snake_case);
     * values are the labels shown in dropdowns.
     */
    public const SOURCES = [
        'website' => 'Website',
        'referral' => 'Referral',
        'walk_in' => 'Walk-in',
        'cold_call' => 'Cold Call',
        'email' => 'Email',
        'social_media' => 'Social Media',
        'exhibition' => 'Exhibition',
        'trade_fair' => 'Trade Fair',
        'partner' => 'Partner',
        'other' => 'Other',
    ];

    public static function sourceOptions(): array
    {
        return collect(self::SOURCES)
            ->map(fn ($label, $key) => ['id' => $key, 'name' => $label])
            ->values()
            ->all();
    }

    public static function sourceLabel(?string $value): string
    {
        if (! $value) {
            return '—';
        }

        return self::SOURCES[$value] ?? ucwords(str_replace('_', ' ', $value));
    }

    protected $fillable = [
        'business_id',
        'code',
        'name',
        'company',
        'phone',
        'email',
        'source',
        'status',
        'assigned_to',
        'expected_value',
        'next_follow_up_at',
        'notes',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'expected_value' => 'decimal:2',
            'next_follow_up_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Lead was {$eventName}");
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_to');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'updated_by');
    }

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'deleted_by');
    }
}
