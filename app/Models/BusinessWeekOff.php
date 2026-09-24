<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class BusinessWeekOff extends Model
{
    use BelongsToBusiness;

    protected $fillable = ['business_id', 'day_of_week', 'is_off'];

    protected function casts(): array
    {
        return ['is_off' => 'boolean'];
    }

    public static array $dayNames = [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
    ];

    public function getDayNameAttribute(): string
    {
        return self::$dayNames[$this->day_of_week] ?? 'Unknown';
    }

    /**
     * Get the set of week-off day numbers for the current business.
     * Falls back to [0] (Sunday) if no config exists.
     *
     * @return array<int>
     */
    public static function offDays(): array
    {
        return static::resolveOffDays(static::all());
    }

    /**
     * Off-days for one business, regardless of the active tenant.
     *
     * THE one definition. It used to be written out separately in the leave
     * form, in AttendanceService and here, and the three did not agree: the
     * form filtered `is_off = true` inside the query and then treated an empty
     * result as "not configured", so a business that had deliberately
     * configured *no* week-offs got Sunday forced back on. The employee's form
     * then valued a Sunday at 0 days while the server valued it at 1, and a
     * combined half-day request could never satisfy its own validation.
     *
     * @return array<int, int> weekday numbers, 0 = Sunday
     */
    public static function offDaysFor(?int $businessId): array
    {
        if (! $businessId) {
            return [0];
        }

        return static::resolveOffDays(
            static::withoutGlobalScopes()
                ->where('business_id', $businessId)
                ->get(['day_of_week', 'is_off'])
        );
    }

    /**
     * No rows at all means the business has never been configured, so the
     * sensible default is Sunday. Rows that all say `is_off = false` mean the
     * business HAS been configured, to work every day — a different answer, and
     * one that must not be overridden.
     *
     * @param  Collection<int, static>  $rows
     * @return array<int, int>
     */
    private static function resolveOffDays($rows): array
    {
        if ($rows->isEmpty()) {
            return [0]; // never configured → Sunday
        }

        return $rows->where('is_off', true)
            ->pluck('day_of_week')
            ->map(fn ($d) => (int) $d)
            ->values()
            ->all();
    }

    /**
     * Save the full week configuration for the current business in one call.
     * $days is an array of day_of_week => is_off (bool).
     */
    public static function saveConfig(array $days): void
    {
        foreach ($days as $dow => $isOff) {
            static::updateOrCreate(
                ['day_of_week' => (int) $dow],
                ['is_off' => (bool) $isOff]
            );
        }
    }
}
