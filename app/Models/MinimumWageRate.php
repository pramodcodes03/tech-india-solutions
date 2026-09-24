<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * One notified minimum-wage rate, for one skill category, from one date.
 *
 * Form B prints the four categories as a banner. Because the banner has to show
 * the rate that applied to the month being printed — not today's — rates are
 * kept as dated revisions and read back with {@see forMonth()}.
 */
class MinimumWageRate extends Model
{
    use BelongsToBusiness, LogsActivity;

    public const CATEGORIES = [
        'highly_skilled' => 'Highly Skilled',
        'skilled' => 'Skilled',
        'semi_skilled' => 'Semi-Skilled',
        'unskilled' => 'Un Skilled',
    ];

    protected $fillable = [
        'business_id', 'effective_from', 'skill_category',
        'basic', 'da', 'overtime_note', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'basic' => 'decimal:2',
            'da' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $event) => "Minimum wage rate was {$event}");
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->skill_category] ?? $this->skill_category;
    }

    /**
     * The rates in force on the last day of a month, keyed by skill category.
     *
     * Takes the newest revision at or before the month end for each category
     * independently, so a mid-year revision to one category does not blank the
     * other three.
     *
     * @return array{date: ?Carbon, rates: array<string, self>}
     */
    public static function forMonth(int $month, int $year): array
    {
        $end = Carbon::create($year, $month, 1)->endOfMonth();

        $rows = static::where('effective_from', '<=', $end)
            ->orderBy('effective_from')
            ->get();

        $rates = [];
        foreach ($rows as $row) {
            $rates[$row->skill_category] = $row;
        }

        $effective = collect($rates)->max(fn (self $r) => $r->effective_from?->timestamp);

        return [
            'date' => $effective ? Carbon::createFromTimestamp($effective) : null,
            'rates' => $rates,
        ];
    }
}
