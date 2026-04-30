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

        // First try the normal scoped lookup (active business).
        $found = $this->newQuery()->where($field, $value)->first();
        if ($found) {
            return $found;
        }

        // Not found in active business. If the requester is Super Admin and
        // the row exists in another business, switch context and return it.
        $admin = Auth::guard('admin')->user();
        if (! $admin || ! $admin->isSuperAdmin()) {
            return null; // → 404 for non-super admins (correct isolation)
        }

        $other = static::withoutGlobalScopes()->where($field, $value)->first();
        if (! $other || ! $other->business_id) {
            return null;
        }

        // Switch session AND in-memory singleton to this row's business.
        // Persist the session immediately so the next request picks it up.
        // No redirect — we just return the resolved model so the controller
        // continues with the correct business context. This avoids any chance
        // of a redirect loop.
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
