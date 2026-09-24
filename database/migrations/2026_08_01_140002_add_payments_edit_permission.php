<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Payments could be created and deleted but never corrected — a typo in the
 * amount, mode or UTR meant deleting and re-recording (losing the payment
 * number). Adds `payments.edit`, granted to every role that can already
 * record a payment.
 */
return new class extends Migration
{
    public function up(): void
    {
        $perm = Permission::firstOrCreate(['name' => 'payments.edit', 'guard_name' => 'admin']);

        if (Permission::where('name', 'payments.create')->where('guard_name', 'admin')->exists()) {
            Role::where('guard_name', 'admin')->get()
                ->filter(fn (Role $role) => $role->hasPermissionTo('payments.create'))
                ->each(fn (Role $role) => $role->givePermissionTo($perm));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('name', 'payments.edit')->where('guard_name', 'admin')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
