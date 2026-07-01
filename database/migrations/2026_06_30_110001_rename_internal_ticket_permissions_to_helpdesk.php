<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Rename internal_tickets.* permissions to helpdesk.* so they appear under a
 * "Helpdesk" module in Roles & Permissions (matching the menu name) and are
 * findable by searching "helpdesk". Grants are carried over; old perms removed.
 */
return new class extends Migration
{
    private array $map = [
        'internal_tickets.view'      => 'helpdesk.view',
        'internal_tickets.manage'    => 'helpdesk.manage',
        'internal_tickets.configure' => 'helpdesk.configure',
    ];

    public function up(): void
    {
        foreach ($this->map as $new) {
            Permission::firstOrCreate(['name' => $new, 'guard_name' => 'admin']);
        }
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        // Carry over grants role-by-role.
        Role::all()->each(function (Role $role) {
            foreach ($this->map as $old => $new) {
                if ($role->hasPermissionTo($old)) {
                    $role->givePermissionTo($new);
                }
            }
        });

        // Remove the old permissions.
        Permission::whereIn('name', array_keys($this->map))->where('guard_name', 'admin')->delete();
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        foreach (array_flip($this->map) as $old) {
            Permission::firstOrCreate(['name' => $old, 'guard_name' => 'admin']);
        }
        Permission::whereIn('name', array_values($this->map))->where('guard_name', 'admin')->delete();
    }
};
