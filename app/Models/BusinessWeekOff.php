<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

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
        $rows = static::all();

        if ($rows->isEmpty()) {
            return [0]; // Sunday default
        }

        return $rows->where('is_off', true)->pluck('day_of_week')->values()->all();
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
