<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private array $perms = [
        'reimbursements.view', 'reimbursements.review',
        'budgets.view', 'budgets.manage',
        'requisitions.view', 'requisitions.create', 'requisitions.approve', 'requisitions.disburse',
    ];

    public function up(): void
    {
        $guard = 'admin';
        $created = [];
        foreach ($this->perms as $name) {
            $created[] = Permission::firstOrCreate(['name' => $name, 'guard_name' => $guard]);
        }
        // Admin + Business Admin get all; Accounts gets the finance subset.
        foreach (['Admin', 'Business Admin'] as $roleName) {
            Role::where(['name' => $roleName, 'guard_name' => $guard])->first()?->givePermissionTo($created);
        }
        $accounts = Role::where(['name' => 'Accounts', 'guard_name' => $guard])->first();
        $accounts?->givePermissionTo($this->perms);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::whereIn('name', $this->perms)->where('guard_name', 'admin')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
