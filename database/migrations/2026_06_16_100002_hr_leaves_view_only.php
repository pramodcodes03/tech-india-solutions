<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        // Leave approval now belongs to Department Heads (via the employee
        // portal). HR Manager becomes view-only on leaves: revoke approve /
        // reject, keep view + create. Applied live so existing HR users lose
        // the buttons without a full RolePermissionSeeder re-run.
        $hr = Role::where('name', 'HR Manager')->where('guard_name', 'admin')->first();
        if ($hr) {
            foreach (['leaves.approve', 'leaves.reject'] as $name) {
                $perm = Permission::where('name', $name)->where('guard_name', 'admin')->first();
                if ($perm && $hr->hasPermissionTo($perm)) {
                    $hr->revokePermissionTo($perm);
                }
            }
        }
    }

    public function down(): void
    {
        // Restore approve/reject to HR Manager on rollback.
        $hr = Role::where('name', 'HR Manager')->where('guard_name', 'admin')->first();
        if ($hr) {
            foreach (['leaves.approve', 'leaves.reject'] as $name) {
                $perm = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'admin']);
                $hr->givePermissionTo($perm);
            }
        }
    }
};
