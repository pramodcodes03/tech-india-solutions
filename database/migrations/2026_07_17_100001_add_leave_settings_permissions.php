<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Give the HR "Leave Policy & Automation Settings" page its own permission group
 * so it can be managed from Roles & Permissions on its own, instead of being
 * folded under Leave Types.
 *
 *   leave_settings.view   → open the Leave Settings page
 *   leave_settings.edit   → save the thresholds / policy
 *   leave_settings.manage → run accrual / year-end jobs on demand
 *
 * Backfilled so nobody loses access: every role that already had the matching
 * leave_types permission gets the new one, and Super Admin gets all three.
 */
return new class extends Migration
{
    public function up(): void
    {
        $guard = 'admin';

        $perms = [];
        foreach (['view', 'edit', 'manage'] as $action) {
            $perms[$action] = Permission::firstOrCreate([
                'name' => "leave_settings.{$action}",
                'guard_name' => $guard,
            ]);
        }

        foreach (Role::where('guard_name', $guard)->get() as $role) {
            if ($role->name === 'Super Admin') {
                $role->givePermissionTo(array_values($perms));
                continue;
            }
            // Leave Settings used to be gated on leave_types.* — mirror that access.
            if ($role->hasPermissionTo('leave_types.view')) {
                $role->givePermissionTo($perms['view']);
            }
            if ($role->hasPermissionTo('leave_types.edit')) {
                $role->givePermissionTo([$perms['edit'], $perms['manage']]);
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('guard_name', 'admin')
            ->whereIn('name', ['leave_settings.view', 'leave_settings.edit', 'leave_settings.manage'])
            ->delete();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
