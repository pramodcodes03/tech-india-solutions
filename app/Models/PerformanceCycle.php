<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A review period. Everything in Module A is scoped to one of these.
 */
class PerformanceCycle extends Model
{
    use BelongsToBusiness, LogsActivity;

    public const FREQUENCIES = [
        'monthly' => 'Monthly',
        'quarterly' => 'Quarterly',
        'half_yearly' => 'Half-Yearly',
        'yearly' => 'Yearly',
    ];

    /** draft → open → locked → closed. Only `open` accepts assessments. */
    public const STATUSES = [
        'draft' => 'Draft',
        'open' => 'Open',
        'locked' => 'Locked',
        'closed' => 'Closed',
    ];

    protected $fillable = [
        'business_id', 'name', 'frequency', 'period_start', 'period_end',
        'self_review_due', 'manager_review_due', 'hr_review_due',
        'status', 'locked_at', 'locked_by', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'self_review_due' => 'date',
            'manager_review_due' => 'date',
            'hr_review_due' => 'date',
            'locked_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $e) => "Performance cycle was {$e}");
    }

    public function employeeKras(): HasMany
    {
        return $this->hasMany(EmployeeKra::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(PerformanceScore::class);
    }

    public function locker(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'locked_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open');
    }

    /** Scores cannot move once a cycle is locked or closed. */
    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'open'], true);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    public function getFrequencyLabelAttribute(): string
    {
        return self::FREQUENCIES[$this->frequency] ?? ucfirst((string) $this->frequency);
    }

    public function getPeriodLabelAttribute(): string
    {
        return $this->period_start->format('d M Y').' — '.$this->period_end->format('d M Y');
    }

    /**
     * The period immediately after this one, used by the roll-over that opens
     * the next cycle and by "copy goals forward".
     *
     * @return array{name:string, period_start:string, period_end:string}
     */
    public function nextPeriod(): array
    {
        $start = $this->period_end->copy()->addDay()->startOfDay();
        $end = match ($this->frequency) {
            'monthly' => $start->copy()->addMonth()->subDay(),
            'quarterly' => $start->copy()->addMonths(3)->subDay(),
            'half_yearly' => $start->copy()->addMonths(6)->subDay(),
            default => $start->copy()->addYear()->subDay(),
        };

        return [
            'name' => self::suggestName($this->frequency, $start),
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
        ];
    }

    /** "Q2 2026", "Aug 2026", "H1 2026", "FY 2026" — a readable default name. */
    public static function suggestName(string $frequency, CarbonInterface $start): string
    {
        return match ($frequency) {
            'monthly' => $start->format('M Y'),
            'quarterly' => 'Q'.(int) ceil($start->month / 3).' '.$start->format('Y'),
            'half_yearly' => 'H'.($start->month <= 6 ? '1' : '2').' '.$start->format('Y'),
            default => 'FY '.$start->format('Y'),
        };
    }
}
