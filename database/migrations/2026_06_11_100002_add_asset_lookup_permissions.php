<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        // Grant the new asset-lookup permissions to roles that already
        // manage assets, so the new admin pages light up immediately for
        // existing super/biz admins without re-running RolePermissionSeeder.
        $perms = [
            'asset_statuses.view', 'asset_statuses.create', 'asset_statuses.edit', 'asset_statuses.delete',
            'asset_maintenance_types.view', 'asset_maintenance_types.create', 'asset_maintenance_types.edit', 'asset_maintenance_types.delete',
        ];

        foreach ($perms as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'admin']);
        }

        foreach (['Super Admin', 'Admin', 'Business Admin'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'admin')->first();
            if ($role) {
                $role->givePermissionTo($perms);
            }
        }

        // Asset Management role (id=10 in the user's screenshot) — grant
        // these too since it's the role that manages the asset module.
        $assetRole = Role::where('name', 'Asset Management')->where('guard_name', 'admin')->first();
        if ($assetRole) {
            $assetRole->givePermissionTo($perms);
        }
    }

    public function down(): void
    {
        // Permissions are intentionally not removed on rollback — they're
        // referenced from app code (the lookup CRUD controllers) and
        // pulling them mid-deploy would 403 everyone.
    }
};
