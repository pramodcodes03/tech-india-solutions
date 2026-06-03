<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Revoke `attendance.edit` from the HR Manager role. Editing punch-in /
 * punch-out times flows directly into payroll (paid_days, LOP, half-day
 * classification), so the capability is now reserved for Admin / Business
 * Admin / Super Admin only. The seeder was updated at the same time so
 * fresh installs grant the same set.
 *
 * Reversible: down() re-adds it.
 */
return new class extends Migration
{
    public function up(): void
    {
        $guard = 'admin';

        $perm = Permission::where([
            'name' => 'attendance.edit',
            'guard_name' => $guard,
        ])->first();

        if (! $perm) {
            return; // Nothing to revoke.
        }

        $hr = Role::where(['name' => 'HR Manager', 'guard_name' => $guard])->first();
        if ($hr) {
            $hr->revokePermissionTo($perm);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $guard = 'admin';

        $perm = Permission::firstOrCreate([
            'name' => 'attendance.edit',
            'guard_name' => $guard,
        ]);

        $hr = Role::where(['name' => 'HR Manager', 'guard_name' => $guard])->first();
        if ($hr) {
            $hr->givePermissionTo($perm);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
