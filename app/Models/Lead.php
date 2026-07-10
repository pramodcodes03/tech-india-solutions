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
     * values are the labels shown in dropdowns. Order here = order in UI.
     *
     * The top block is the customer's marketing-channel list — covers the
     * specific portals / ad networks / events they actually use to generate
     * leads. The bottom block keeps generic catch-alls (website, referral,
     * walk-in, etc.) for leads that don't fit a specific channel.
     */
    public const SOURCES = [
        // ── Marketplace / B2B portals ─────────────────────────────────────
        'indiamart'          => 'IndiaMART',
        'industrybuying'     => 'IndustryBuying',
        'exportersindia'     => 'ExportersIndia',
        'google_my_business' => 'Google My Business (GMB)',
        // ── Paid advertising ──────────────────────────────────────────────
        'google_ads'    => 'Google Ads',
        'meta_ads'      => 'Meta Ads',
        'instagram_ads' => 'Instagram Ads',
        'youtube_ads'   => 'YouTube Ads',
        // ── Direct inbound ────────────────────────────────────────────────
        'whatsapp'     => 'WhatsApp',
        'contact_form' => 'Contact Form',
        // ── Events ────────────────────────────────────────────────────────
        'seminar' => 'Seminar',
        'webinar' => 'Webinar',
        // ── Referrals ─────────────────────────────────────────────────────
        'employee_reference' => 'Employee Reference',
        // ── Government / institutional ────────────────────────────────────
        'gem_portal' => 'Government Portal (GeM)',
        // ── Generic catch-alls (kept for leads that don't fit above) ──────
        'website'   => 'Website',
        'referral'  => 'Referral',
        'walk_in'   => 'Walk-in',
        'cold_call' => 'Cold Call',
        'email'     => 'Email',
        'partner'   => 'Partner',
        'other'     => 'Other',
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
        'city',
        'state',
        'bid_number',
        'ra_emd',
        'source',
        'product_id',
        'lead_date',
        'status',
        'last_stage_changed_at',
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
            'lead_date' => 'date',
            'last_stage_changed_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stageLogs(): HasMany
    {
        return $this->hasMany(LeadStageLog::class)->latest();
    }

    /** Total age of the lead in days since it was received/created. */
    public function getAgeInDaysAttribute(): int
    {
        $start = $this->lead_date ?? $this->created_at;

        return $start ? (int) $start->diffInDays(now()) : 0;
    }

    /** Time spent in the current stage, human-readable. */
    public function getTimeInStageAttribute(): string
    {
        $since = $this->last_stage_changed_at ?? $this->created_at;

        return $since ? $since->diffForHumans(null, true) : '—';
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
