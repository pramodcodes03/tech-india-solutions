<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * In-app inbox notification for a specific admin user.
 *
 * Not scoped via BelongsToBusiness because a Super Admin (business_id=null)
 * also receives notifications across businesses; we filter by admin_id
 * directly when reading.
 */
class AdminNotification extends Model
{
    protected $fillable = [
        'business_id', 'admin_id', 'event_key',
        'title', 'body', 'link',
        'related_type', 'related_id',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    public function markAsRead(): bool
    {
        if ($this->read_at) {
            return false;
        }
        $this->read_at = now();

        return $this->save();
    }

    /* ──────────────── Scopes ──────────────── */

    public function scopeForAdmin(Builder $q, Admin $admin): Builder
    {
        return $q->where('admin_id', $admin->id);
    }

    public function scopeUnread(Builder $q): Builder
    {
        return $q->whereNull('read_at');
    }
}
