<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToBusiness;
use App\Support\Tenancy\CurrentBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class DieselEntry extends Model
{
    use BelongsToBusiness, LogsActivity;

    protected $fillable = [
        'business_id', 'serial_no', 'entry_date', 'entry_time', 'bill_no', 'slip_no',
        'vehicle_no', 'quantity', 'amount', 'rate_per_litre', 'attachment', 'remarks', 'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'quantity' => 'decimal:2',
            'amount' => 'decimal:2',
            'rate_per_litre' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $event) => "Diesel entry was {$event}");
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'recorded_by');
    }

    /**
     * Next serial for a month, e.g. DSL-202608-0004.
     *
     * Scoped per business and per month so two businesses never collide on the
     * (business_id, serial_no) unique index and the number restarts each month,
     * which is how a fuel register is normally kept.
     */
    public static function nextSerial(?string $date = null, ?int $businessId = null): string
    {
        $month = ($date ? Carbon::parse($date) : now())->format('Ym');
        $businessId ??= app(CurrentBusiness::class)->id();

        $last = static::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->where('serial_no', 'like', "DSL-{$month}-%")
            ->orderByDesc('serial_no')
            ->value('serial_no');

        $next = $last ? ((int) substr($last, -4)) + 1 : 1;

        return sprintf('DSL-%s-%04d', $month, $next);
    }
}
