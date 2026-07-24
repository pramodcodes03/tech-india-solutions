<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $guard = 'admin';
        $perm = Permission::firstOrCreate(['name' => 'bulk_imports.run', 'guard_name' => $guard]);
        foreach (['Admin', 'Business Admin', 'HR Manager'] as $roleName) {
            Role::where(['name' => $roleName, 'guard_name' => $guard])->first()?->givePermissionTo($perm);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where(['name' => 'bulk_imports.run', 'guard_name' => 'admin'])->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
