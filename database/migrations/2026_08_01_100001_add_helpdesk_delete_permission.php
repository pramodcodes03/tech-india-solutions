<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Helpdesk tickets had no delete action at all. Add `helpdesk.delete` and
 * grant it to every role that can already manage tickets, so admins can
 * remove test/junk tickets. (warnings.delete / penalties.delete already
 * exist from the original seeder — only routes/UI were missing for those.)
 */
return new class extends Migration
{
    public function up(): void
    {
        $perm = Permission::firstOrCreate(['name' => 'helpdesk.delete', 'guard_name' => 'admin']);

        if (Permission::where('name', 'helpdesk.manage')->where('guard_name', 'admin')->exists()) {
            Role::where('guard_name', 'admin')->get()
                ->filter(fn (Role $role) => $role->hasPermissionTo('helpdesk.manage'))
                ->each(fn (Role $role) => $role->givePermissionTo($perm));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('name', 'helpdesk.delete')->where('guard_name', 'admin')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
