<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private array $perms = [
        'employee_documents.view',
        'employee_documents.upload',
        'employee_documents.verify',
        'employee_documents.delete',
    ];

    public function up(): void
    {
        $guard = 'admin';
        $created = [];
        foreach ($this->perms as $name) {
            $created[] = Permission::firstOrCreate(['name' => $name, 'guard_name' => $guard]);
        }
        foreach (['Admin', 'Business Admin', 'HR Manager'] as $roleName) {
            Role::where(['name' => $roleName, 'guard_name' => $guard])->first()?->givePermissionTo($created);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::whereIn('name', $this->perms)->where('guard_name', 'admin')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
