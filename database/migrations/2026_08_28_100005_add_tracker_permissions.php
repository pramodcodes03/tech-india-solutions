<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permissions for the three operational trackers.
 *
 * Each tracker is its own module row in the Roles & Permissions matrix so a
 * business can, for example, give the front desk the Visitor register only and
 * keep Diesel and Break Sheet away from them — that "role-based access so only
 * designated users see each register" is the point of the trackers.
 *
 * Granted here to Admin / Business Admin / HR Manager (Super Admin passes
 * through Gate::before). Everyone else starts with nothing and is opted in from
 * the Roles screen.
 */
return new class extends Migration
{
    private array $modules = [
        'break_tracker' => ['view', 'create', 'edit', 'delete', 'import', 'export'],
        'diesel_tracker' => ['view', 'create', 'edit', 'delete', 'import', 'export', 'manage_budget'],
        'visitor_tracker' => ['view', 'create', 'edit', 'delete', 'import', 'export'],
        'tracker_settings' => ['view', 'manage'],
    ];

    public function up(): void
    {
        $guard = 'admin';

        $names = [];
        foreach ($this->modules as $module => $actions) {
            foreach ($actions as $action) {
                $names[] = "{$module}.{$action}";
                Permission::firstOrCreate(['name' => "{$module}.{$action}", 'guard_name' => $guard]);
            }
        }

        foreach (['Super Admin', 'Admin', 'Business Admin', 'HR Manager'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', $guard)->first();
            $role?->givePermissionTo($names);
        }

        // Viewer mirrors its existing "every *.view" rule.
        $viewer = Role::where('name', 'Viewer')->where('guard_name', $guard)->first();
        $viewer?->givePermissionTo(array_filter($names, fn ($n) => str_ends_with($n, '.view')));

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        $names = [];
        foreach ($this->modules as $module => $actions) {
            foreach ($actions as $action) {
                $names[] = "{$module}.{$action}";
            }
        }

        Permission::where('guard_name', 'admin')->whereIn('name', $names)->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
