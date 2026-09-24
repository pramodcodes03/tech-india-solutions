<?php

namespace App\Support;

use App\Models\Admin;
use Illuminate\Support\Facades\Auth;

/**
 * Display-only masking of the Super Admin identity.
 *
 * Anything a Super Admin does shows up as "System Admin" to everyone else —
 * in the Recent Activity log, on approval trails, and anywhere else a name is
 * printed. The underlying record is untouched: `activity_log.causer_id`,
 * `approver_id`, `issued_by` and friends still point at the real account, so a
 * compliance audit of the database sees exactly who acted.
 *
 * Who sees what:
 *   - A Super Admin sees real names — otherwise they could not audit at all.
 *   - Every other admin, and every employee, sees "System Admin".
 *
 * This is presentation only. It is deliberately NOT a query scope: filtering
 * rows out would change reports and counts, and the requirement is to hide the
 * identity, not the action.
 */
class SuperAdminMask
{
    public const LABEL = 'System Admin';

    /**
     * Is masking in force for whoever is looking at this page?
     *
     * Deliberately not cached in a static: the authenticated user changes
     * between requests inside one process (tests, queue workers, console), and
     * a stale cache would leak a real name to the wrong viewer. The check is
     * cheap — the viewer's roles are loaded once onto their model instance and
     * reused for every subsequent call.
     */
    public static function active(): bool
    {
        return ! (Auth::guard('admin')->user()?->isSuperAdmin() ?? false);
    }

    /**
     * Name to print for an actor, whoever they are.
     *
     * Accepts an Admin, an Employee, or null — activity-log causers and the
     * various `creator` / `approver` / `issuer` relations are not all the same
     * type, and several are nullable.
     */
    public static function label(?object $subject, string $fallback = 'System'): string
    {
        if (! $subject) {
            return $fallback;
        }

        if ($subject instanceof Admin) {
            return self::masks($subject) ? self::LABEL : (string) $subject->name;
        }

        // Employees and anything else: print their own name, unmasked.
        return (string) ($subject->full_name ?? $subject->name ?? $fallback);
    }

    /** First letter for an avatar bubble, matching whatever label() returns. */
    public static function initial(?object $subject, string $fallback = 'S'): string
    {
        $label = self::label($subject, $fallback);

        return strtoupper(substr($label, 0, 1));
    }

    /** Should this particular admin's identity be hidden from the viewer? */
    public static function masks(?Admin $admin): bool
    {
        return $admin !== null && self::active() && $admin->isSuperAdmin();
    }

    /**
     * Role name to print. The Super Admin role itself is renamed rather than
     * hidden here — callers that list roles for selection already exclude it.
     */
    public static function roleLabel(string $role): string
    {
        return ($role === 'Super Admin' && self::active()) ? self::LABEL : $role;
    }
}
