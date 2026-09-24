<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Module E permissions.
 *
 * The registers print wages, fines and advances for the whole workforce, so
 * they get their own pack rather than riding on documents_payroll: a compliance
 * officer may need to file the returns without being able to open payroll.
 *
 *   documents_statutory.*   the ten statutory register PDFs
 *   statutory_registers.*   the data behind them — fines, advances, damage,
 *                           deductions, overtime, child labour, wage rates
 *   statutory_registers.configure  the establishment identity block
 */
return new class extends Migration
{
    private array $modules = [
        'documents_statutory' => ['view', 'generate'],
        'statutory_registers' => ['view', 'create', 'edit', 'delete', 'configure'],
    ];

    public function up(): void
    {
        $names = [];
        foreach ($this->modules as $module => $actions) {
            foreach ($actions as $action) {
                $names[] = "{$module}.{$action}";
                Permission::firstOrCreate(['name' => "{$module}.{$action}", 'guard_name' => 'admin']);
            }
        }

        foreach (['Super Admin', 'Admin', 'Business Admin'] as $roleName) {
            Role::where('name', $roleName)->where('guard_name', 'admin')->first()?->givePermissionTo($names);
        }

        // HR keeps the registers day to day, but not the establishment identity.
        Role::where('name', 'HR Manager')->where('guard_name', 'admin')->first()?->givePermissionTo([
            'documents_statutory.view', 'documents_statutory.generate',
            'statutory_registers.view', 'statutory_registers.create',
            'statutory_registers.edit', 'statutory_registers.delete',
        ]);

        // Accounts files the returns; it does not edit them.
        Role::where('name', 'Accounts')->where('guard_name', 'admin')->first()?->givePermissionTo([
            'documents_statutory.view', 'documents_statutory.generate',
            'statutory_registers.view',
        ]);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        $names = [];
        foreach ($this->modules as $module => $actions) {
            foreach ($actions as $action) {
                $names[] = "{$module}.{$action}";
            }
        }

        Permission::where('guard_name', 'admin')->whereIn('name', $names)->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
