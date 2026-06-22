<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        // Grant the new requisition-category permissions to roles that already
        // manage requisitions/finance, so the new admin page lights up
        // immediately for existing admins without re-running the seeder.
        $perms = [
            'requisition_categories.view',
            'requisition_categories.create',
            'requisition_categories.edit',
            'requisition_categories.delete',
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
    }

    public function down(): void
    {
        // Permissions are intentionally not removed on rollback — they're
        // referenced from app code (the lookup CRUD controller) and pulling
        // them mid-deploy would 403 everyone.
    }
};
