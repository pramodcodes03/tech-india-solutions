<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $guard = 'admin';

        // Create the dedicated Locations (States & Cities) permissions.
        $perms = [];
        foreach (['view', 'create', 'edit', 'delete'] as $action) {
            $perms[$action] = Permission::firstOrCreate([
                'name' => "locations.{$action}",
                'guard_name' => $guard,
            ]);
        }

        // Backfill so no role loses access: Locations used to be gated on
        // settings.view / settings.edit. Grant the matching new permission to
        // every role that already held those.
        foreach (Role::where('guard_name', $guard)->get() as $role) {
            // Super Admin holds everything.
            if ($role->name === 'Super Admin') {
                $role->givePermissionTo(array_values($perms));
                continue;
            }
            if ($role->hasPermissionTo("settings.view")) {
                $role->givePermissionTo($perms['view']);
            }
            if ($role->hasPermissionTo("settings.edit")) {
                $role->givePermissionTo([$perms['create'], $perms['edit'], $perms['delete']]);
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('guard_name', 'admin')
            ->whereIn('name', ['locations.view', 'locations.create', 'locations.edit', 'locations.delete'])
            ->delete();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
