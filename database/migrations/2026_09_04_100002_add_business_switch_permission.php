<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * `businesses.switch` — the right to move between the businesses an admin has
 * been assigned.
 *
 * Deliberately separate from `businesses.view` (which is about the Businesses
 * admin screen) so granting someone the ability to read a company's invoices
 * does not also let them edit the company record.
 */
return new class extends Migration
{
    public function up(): void
    {
        Permission::firstOrCreate(['name' => 'businesses.switch', 'guard_name' => 'admin']);

        foreach (['Super Admin', 'Admin', 'Business Admin', 'Accounts'] as $roleName) {
            Role::where('name', $roleName)->where('guard_name', 'admin')->first()
                ?->givePermissionTo('businesses.switch');
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('guard_name', 'admin')->where('name', 'businesses.switch')->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
