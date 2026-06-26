<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Granular per-report permissions so a role can be given access to specific
 * reports only (instead of the single blanket reports.view). Existing roles
 * that had reports.view keep full access — they're granted every new permission
 * so nothing is lost; admins can then un-tick the reports a role shouldn't see.
 */
return new class extends Migration
{
    private array $perms = [
        'reports.sales',
        'reports.inventory',
        'reports.customers',
        'reports.purchases',
        'reports.payments',
        'reports.builder',
        'reports.hr',
    ];

    public function up(): void
    {
        foreach ($this->perms as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'admin']);
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        // Preserve current access: any role that could view reports keeps all of them.
        Role::whereHas('permissions', fn ($q) => $q->where('name', 'reports.view'))
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo($this->perms));
    }

    public function down(): void
    {
        Permission::whereIn('name', $this->perms)->where('guard_name', 'admin')->delete();
    }
};
