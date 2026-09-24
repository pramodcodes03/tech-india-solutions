<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Delete actions for Comp-Off Requests and Attendance Corrections — neither
 * screen had any way to remove a stale/test row.
 *
 *  - leaves.delete                  → governs comp-off request deletion
 *    (comp-off lives under the Leaves permission umbrella; leave requests
 *     themselves still have no delete action).
 *  - attendance_corrections.delete  → governs correction request deletion.
 *
 * Each is granted to the roles that already hold the matching decision
 * permission, so nobody silently gains a destructive action.
 */
return new class extends Migration
{
    /** new permission => permission whose holders should inherit it */
    private array $map = [
        'leaves.delete' => 'leaves.approve',
        'attendance_corrections.delete' => 'attendance_corrections.manage',
    ];

    public function up(): void
    {
        foreach ($this->map as $new => $basedOn) {
            $perm = Permission::firstOrCreate(['name' => $new, 'guard_name' => 'admin']);

            if (! Permission::where('name', $basedOn)->where('guard_name', 'admin')->exists()) {
                continue;
            }

            Role::where('guard_name', 'admin')->get()
                ->filter(fn (Role $role) => $role->hasPermissionTo($basedOn))
                ->each(fn (Role $role) => $role->givePermissionTo($perm));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::whereIn('name', array_keys($this->map))->where('guard_name', 'admin')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
