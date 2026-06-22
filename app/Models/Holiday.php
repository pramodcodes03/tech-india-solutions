<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Holiday extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'name', 'date', 'type', 'is_yearly', 'is_dynamic', 'employee_id',
        'description', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date'       => 'date',
            'is_yearly'  => 'boolean',
            'is_dynamic' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Build a Carbon-aware date collection for a given year,
     * expanding yearly holidays to that year's date.
     */
    public static function forYear(int $year): \Illuminate\Support\Collection
    {
        return static::get()->flatMap(function ($h) use ($year) {
            if ($h->is_yearly) {
                // Recreate the date in the requested year (keep month+day)
                $newDate = \Carbon\Carbon::create($year, $h->date->month, $h->date->day);
                $clone = $h->replicate();
                $clone->date = $newDate;
                // Keep the original primary key on the projected occurrence so
                // Edit / Remove on a yearly holiday target the real stored record
                // (replicate() drops the key, which hid the action buttons).
                $clone->id = $h->id;
                return [$clone];
            }
            if ($h->date->year === $year) {
                return [$h];
            }
            return [];
        });
    }
}
