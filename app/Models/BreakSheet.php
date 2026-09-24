<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class BreakSheet extends Model
{
    use BelongsToBusiness, LogsActivity;

    protected $fillable = [
        'business_id', 'employee_id', 'break_date', 'out_time', 'in_time',
        'duration_minutes', 'break_type_id', 'remarks', 'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'break_date' => 'date',
            'duration_minutes' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $event) => "Break sheet entry was {$event}");
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function breakType(): BelongsTo
    {
        return $this->belongsTo(TrackerOption::class, 'break_type_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'recorded_by');
    }

    /**
     * Minutes between out and in. A break that ends earlier in the clock than it
     * started has crossed midnight (a night-shift break), so roll it a day
     * forward instead of returning a negative duration.
     *
     * Returns null while the employee is still out — nothing to total yet.
     */
    public static function minutesBetween(?string $out, ?string $in): ?int
    {
        if (! $out || ! $in) {
            return null;
        }

        $start = Carbon::createFromFormat('H:i:s', self::normalizeTime($out));
        $end = Carbon::createFromFormat('H:i:s', self::normalizeTime($in));

        if ($end->lessThan($start)) {
            $end->addDay();
        }

        return (int) $start->diffInMinutes($end);
    }

    /** Accept "14:05" and "14:05:00" alike — HTML time inputs send the short form. */
    private static function normalizeTime(string $value): string
    {
        return substr($value, 0, 8) === $value && substr_count($value, ':') === 2
            ? $value
            : substr($value, 0, 5).':00';
    }

    /**
     * Combined break time per employee per day, for whatever set the caller's
     * query describes.
     *
     * The register logs one row per break segment, but HR reads the sheet the
     * other way round: "how long was this person off the floor today?" That
     * answer is the SUM of their segments for the date, which no single row
     * carries. Aggregating in SQL over the whole filtered set — rather than
     * summing the rows of the current page — is deliberate: a person's four
     * breaks can straddle a page boundary or a sort order, and a total that
     * changed with pagination would be worse than none.
     *
     * Keyed "<employee_id>|<Y-m-d>" so a view can look a row's group up in
     * O(1) while it renders.
     *
     * @return array<string, array{minutes:int, entries:int}>
     */
    public static function dailyTotals(EloquentBuilder $query): array
    {
        return $query
            ->clone()
            ->reorder()
            ->groupBy('break_sheets.employee_id', 'break_sheets.break_date')
            ->selectRaw('break_sheets.employee_id,
                         break_sheets.break_date,
                         COUNT(*) as entries,
                         COALESCE(SUM(break_sheets.duration_minutes),0) as minutes')
            ->get()
            ->mapWithKeys(fn ($row) => [
                self::groupKey((int) $row->employee_id, (string) $row->break_date) => [
                    'minutes' => (int) $row->minutes,
                    'entries' => (int) $row->entries,
                ],
            ])
            ->all();
    }

    /** The key {@see dailyTotals()} returns, from either a row or raw values. */
    public static function groupKey(int $employeeId, string $date): string
    {
        return $employeeId.'|'.substr($date, 0, 10);
    }

    /** This entry's key into a {@see dailyTotals()} map. */
    public function getGroupKeyAttribute(): string
    {
        return self::groupKey((int) $this->employee_id, $this->break_date->toDateString());
    }

    /** "1h 25m" — the format the register and the analytics view both print. */
    public function getDurationLabelAttribute(): string
    {
        return self::formatMinutes($this->duration_minutes);
    }

    public static function formatMinutes(?float $minutes): string
    {
        if ($minutes === null) {
            return '—';
        }

        $minutes = (int) round($minutes);
        $hours = intdiv($minutes, 60);
        $rest = $minutes % 60;

        return $hours > 0 ? "{$hours}h {$rest}m" : "{$rest}m";
    }
}
