<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Add a dedicated permission for the global Analytics Dashboard.
 *
 * The generic `dashboard.view` toggle was overloaded — it gated nothing in
 * practice, so the financial Analytics dashboard was visible to anyone who
 * could log in to the admin panel. This migration:
 *
 *   1. Creates a NEW permission `analytics_dashboard.view`.
 *   2. Grants it to the Admin + Business Admin roles only (Super Admin
 *      bypasses every permission via Gate::before, so no row is needed).
 *
 * Existing HR Manager / Sales / Inventory / Accounts / Service / Viewer
 * roles are intentionally NOT granted the new permission — admins can opt
 * them in from Settings → Roles & Permissions.
 */
return new class extends Migration
{
    public function up(): void
    {
        $guard = 'admin';

        $perm = Permission::firstOrCreate([
            'name' => 'analytics_dashboard.view',
            'guard_name' => $guard,
        ]);

        foreach (['Admin', 'Business Admin'] as $roleName) {
            $role = Role::where(['name' => $roleName, 'guard_name' => $guard])->first();
            if ($role) {
                $role->givePermissionTo($perm);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $perm = Permission::where([
            'name' => 'analytics_dashboard.view',
            'guard_name' => 'admin',
        ])->first();

        if ($perm) {
            // Detaches from every role/user automatically via Spatie cascade.
            $perm->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
