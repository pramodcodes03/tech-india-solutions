<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Make each report its OWN permission module so the Roles matrix shows one row
 * per report (Report Sales, Report Inventory, …) instead of scattering them as
 * action columns under a single "Reports" module. Migrates grants from the
 * earlier action-style perms (reports.sales, …) and removes them.
 */
return new class extends Migration
{
    private array $map = [
        'reports.sales'      => 'report_sales.view',
        'reports.inventory'  => 'report_inventory.view',
        'reports.customers'  => 'report_customers.view',
        'reports.purchases'  => 'report_purchases.view',
        'reports.payments'   => 'report_payments.view',
        'reports.hr'         => 'report_hr.view',
        'reports.builder'    => 'report_builder.view',
    ];

    public function up(): void
    {
        foreach ($this->map as $new) {
            Permission::firstOrCreate(['name' => $new, 'guard_name' => 'admin']);
        }
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        // Carry over grants: a role that could see a report (or had blanket
        // reports.view) gets the new per-report permission.
        Role::all()->each(function (Role $role) {
            foreach ($this->map as $old => $new) {
                if ($role->hasPermissionTo($old) || $role->hasPermissionTo('reports.view')) {
                    $role->givePermissionTo($new);
                }
            }
        });

        // Drop the old action-style report perms (keep reports.view / reports.export).
        Permission::whereIn('name', array_keys($this->map))->where('guard_name', 'admin')->delete();
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::whereIn('name', array_values($this->map))->where('guard_name', 'admin')->delete();
    }
};
