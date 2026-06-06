<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Recruitment module permissions. Granted to Admin, Business Admin and
 * HR Manager. Super Admin bypasses via Gate::before.
 */
return new class extends Migration
{
    private array $perms = [
        'recruitment.view',
        'recruitment.create',
        'recruitment.edit',
        'recruitment.delete',
        'recruitment.manage_stages',
    ];

    public function up(): void
    {
        $guard = 'admin';

        $created = [];
        foreach ($this->perms as $name) {
            $created[] = Permission::firstOrCreate(['name' => $name, 'guard_name' => $guard]);
        }

        foreach (['Admin', 'Business Admin', 'HR Manager'] as $roleName) {
            $role = Role::where(['name' => $roleName, 'guard_name' => $guard])->first();
            if ($role) {
                $role->givePermissionTo($created);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::whereIn('name', $this->perms)->where('guard_name', 'admin')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
