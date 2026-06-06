<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private array $perms = [
        'attendance_corrections.view',
        'attendance_corrections.manage',
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
            $role?->givePermissionTo($created);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::whereIn('name', $this->perms)->where('guard_name', 'admin')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
