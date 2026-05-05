<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * NotificationLog is intentionally NOT scoped via BelongsToBusiness — super
 * admins need cross-business visibility into delivery problems. The admin
 * UI filters by current business explicitly when needed.
 */
class NotificationLog extends Model
{
    protected $fillable = [
        'business_id', 'event_key', 'subject',
        'recipient_email', 'recipient_name',
        'related_type', 'related_id',
        'status', 'error', 'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function related(): MorphTo
    {
        return $this->morphTo();
    }
}
