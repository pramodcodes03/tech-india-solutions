<?php

namespace App\Support\Tenancy;

use App\Models\Business;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait BelongsToBusiness
{
    public static function bootBelongsToBusiness(): void
    {
        static::addGlobalScope(new BusinessScope);

        static::creating(function ($model) {
            if (! $model->business_id) {
                $current = app(CurrentBusiness::class);
                if ($current->id()) {
                    $model->business_id = $current->id();
                }
            }
        });
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Override Laravel's route-model binding to handle the super-admin
     * cross-business case gracefully:
     *
     *   - Regular admin visits a record in another business → 404 (correct: isolation)
     *   - Super admin visits a record in another business → switch session to
     *     that record's business and ALSO bypass the scope on this request so
     *     we can return the model immediately. No redirect = no loop possible.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $field = $field ?? $this->getRouteKeyName();

        // SubstituteBindings runs before the `business` middleware, so
        // CurrentBusiness may not be hydrated yet. Without it, BusinessScope
        // fails closed and 404s every binding for non-super admins.
        // Hydrate it the same way EnsureBusinessContext would.
        $current = app(CurrentBusiness::class);
        if (! $current->id()) {
            $admin = Auth::guard('admin')->user();
            $employee = Auth::guard('employee')->user();

            $businessId = match (true) {
                (bool) ($admin && ! $admin->isSuperAdmin() && $admin->business_id) => $admin->business_id,
                (bool) ($employee && $employee->business_id) => $employee->business_id,
                (bool) ($admin && $admin->isSuperAdmin() && session('business_id')) => session('business_id'),
                default => null,
            };

            if ($businessId && $business = Business::find($businessId)) {
                $current->setWithoutSession($business);
            }
        }

        // First try the normal scoped lookup (active business).
        $found = $this->newQuery()->where($field, $value)->first();
        if ($found) {
            return $found;
        }

        $admin = Auth::guard('admin')->user();
        $employee = Auth::guard('employee')->user();

        // Not authenticated yet. SubstituteBindings runs BEFORE the auth
        // middleware in Laravel 11's web pipeline, so returning null here
        // would 404 the request before the auth middleware ever gets to
        // redirect to the login page. Fall back to an unscoped lookup so
        // the route binding succeeds; auth:admin / auth:employee will fire
        // immediately after and bounce the visitor to the login page.
        if (! $admin && ! $employee) {
            return static::withoutGlobalScopes()->where($field, $value)->first();
        }

        // Authenticated, but the row isn't in the active business.
        // - Super Admin: auto-switch to the row's business and return it.
        // - Anyone else: tenant isolation kicks in, return null → 404.
        if (! $admin || ! $admin->isSuperAdmin()) {
            return null;
        }

        $other = static::withoutGlobalScopes()->where($field, $value)->first();
        if (! $other || ! $other->business_id) {
            return null;
        }

        $business = Business::find($other->business_id);
        if ($business) {
            app(CurrentBusiness::class)->setWithoutSession($business);
        }

        session(['business_id' => $other->business_id]);
        session()->flash('info', "Switched to {$business?->name} to view this record.");
        session()->save();

        return $other;
    }
}
