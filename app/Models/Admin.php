<?php

namespace App\Models;

use App\Support\SuperAdminMask;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\Traits\HasRoles;

class Admin extends Authenticatable
{
    use HasRoles, LogsActivity, Notifiable, SoftDeletes;

    protected string $guard_name = 'admin';

    protected $fillable = [
        'business_id',
        'name',
        'email',
        'password',
        'phone',
        'status',
        'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'last_login_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Admin was {$eventName}");
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Businesses this admin has been granted access to on top of their own.
     *
     * @see database/migrations/2026_09_04_100001_create_admin_business_table.php
     */
    public function businesses(): BelongsToMany
    {
        return $this->belongsToMany(Business::class, 'admin_business')
            ->withPivot('granted_by')
            ->withTimestamps();
    }

    /**
     * Every business this admin may work in, home business first.
     *
     * A Super Admin is not special-cased here — callers that need the
     * everything-everywhere behaviour check {@see isSuperAdmin()} themselves,
     * so this method always answers the narrower, assignment-based question.
     *
     * @return Collection<int, Business>
     */
    public function accessibleBusinesses(): Collection
    {
        $assigned = $this->businesses()->where('is_active', true)->get();

        if ($this->business_id && ! $assigned->contains('id', $this->business_id)) {
            $home = Business::find($this->business_id);
            if ($home && $home->is_active) {
                $assigned->prepend($home);
            }
        }

        return $assigned->sortBy('name')->values();
    }

    /**
     * May this admin work in the given business?
     *
     * True for their home business, for anything explicitly assigned, and for
     * everything if they are a Super Admin.
     */
    public function canAccessBusiness(?int $businessId): bool
    {
        if (! $businessId) {
            return false;
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        if ((int) $this->business_id === (int) $businessId) {
            return true;
        }

        return $this->businesses()->whereKey($businessId)->exists();
    }

    /**
     * Helpdesk departments this admin is restricted to.
     *
     * Empty means unrestricted, not "none" — the restriction is opt-in, so
     * existing admins keep working and a new one is narrowed deliberately.
     *
     * @return array<int, string>
     */
    public function helpdeskDepartments(): array
    {
        if ($this->isSuperAdmin()) {
            return [];
        }

        return DB::table('admin_ticket_departments')
            ->where('admin_id', $this->id)
            ->pluck('department')
            ->all();
    }

    /** Can this admin see helpdesk tickets for the given department? */
    public function canSeeHelpdeskDepartment(?string $department): bool
    {
        $allowed = $this->helpdeskDepartments();

        return $allowed === [] || in_array($department, $allowed, true);
    }

    /**
     * Whether to offer the business switcher at all — there is nothing to
     * switch between when an admin only has their own business.
     */
    public function canSwitchBusiness(): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->can('businesses.switch')
            && $this->accessibleBusinesses()->count() > 1;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('Super Admin');
    }

    /**
     * The name to show on screen. A Super Admin reads as "System Admin" to
     * everyone except another Super Admin; the stored `name` is untouched.
     *
     * @see SuperAdminMask
     */
    public function getDisplayNameAttribute(): string
    {
        return SuperAdminMask::label($this, (string) $this->name);
    }
}
