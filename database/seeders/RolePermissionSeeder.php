<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $guard = 'admin';

        // ── Define all permissions ──────────────────────────────────────
        $modules = [
            'dashboard' => ['view'],
            'users' => ['view', 'create', 'edit', 'delete'],
            'roles' => ['view', 'create', 'edit', 'delete'],
            'customers' => ['view', 'create', 'edit', 'delete'],
            'leads' => ['view', 'create', 'edit', 'delete', 'convert'],
            'quotations' => ['view', 'create', 'edit', 'delete', 'export_pdf'],
            'sales_orders' => ['view', 'create', 'edit', 'delete'],
            'products' => ['view', 'create', 'edit', 'delete'],
            'categories' => ['view', 'create', 'edit', 'delete'],
            'inventory' => ['view', 'create', 'adjust'],
            'warehouses' => ['view', 'create', 'edit', 'delete'],
            'vendors' => ['view', 'create', 'edit', 'delete'],
            'purchase_orders' => ['view', 'create', 'edit', 'delete'],
            'goods_receipts' => ['view', 'create'],
            'invoices' => ['view', 'create', 'edit', 'delete', 'export_pdf'],
            'payments' => ['view', 'create', 'delete'],
            'service_tickets' => ['view', 'create', 'edit', 'delete'],
            'reports' => ['view', 'export'],
            'settings' => ['view', 'edit'],
        ];

        // Create all permissions
        $allPermissions = [];
        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                $permissionName = "{$module}.{$action}";
                Permission::firstOrCreate([
                    'name' => $permissionName,
                    'guard_name' => $guard,
                ]);
                $allPermissions[] = $permissionName;
            }
        }

        // ── Create roles ────────────────────────────────────────────────

        // Super Admin - gets all permissions via Gate::before, no explicit assignment
        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => $guard]);

        // Admin - all permissions except users.delete, roles.delete
        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => $guard]);
        $adminPermissions = array_filter($allPermissions, function ($perm) {
            return ! in_array($perm, ['users.delete', 'roles.delete']);
        });
        $adminRole->syncPermissions($adminPermissions);

        // Sales
        $salesRole = Role::firstOrCreate(['name' => 'Sales', 'guard_name' => $guard]);
        $salesPermissions = array_merge(
            ['dashboard.view'],
            $this->allActionsFor('customers', $modules),
            $this->allActionsFor('leads', $modules),
            $this->allActionsFor('quotations', $modules),
            $this->allActionsFor('sales_orders', $modules),
            ['invoices.view', 'payments.view', 'reports.view']
        );
        $salesRole->syncPermissions($salesPermissions);

        // Inventory
        $inventoryRole = Role::firstOrCreate(['name' => 'Inventory', 'guard_name' => $guard]);
        $inventoryPermissions = array_merge(
            ['dashboard.view'],
            $this->allActionsFor('products', $modules),
            $this->allActionsFor('categories', $modules),
            $this->allActionsFor('inventory', $modules),
            $this->allActionsFor('warehouses', $modules),
            ['vendors.view'],
            $this->allActionsFor('purchase_orders', $modules),
            $this->allActionsFor('goods_receipts', $modules),
            ['reports.view']
        );
        $inventoryRole->syncPermissions($inventoryPermissions);

        // Accounts
        $accountsRole = Role::firstOrCreate(['name' => 'Accounts', 'guard_name' => $guard]);
        $accountsPermissions = array_merge(
            ['dashboard.view'],
            $this->allActionsFor('invoices', $modules),
            $this->allActionsFor('payments', $modules),
            ['customers.view'],
            $this->allActionsFor('reports', $modules),
            ['settings.view']
        );
        $accountsRole->syncPermissions($accountsPermissions);

        // Service
        $serviceRole = Role::firstOrCreate(['name' => 'Service', 'guard_name' => $guard]);
        $servicePermissions = array_merge(
            ['dashboard.view'],
            $this->allActionsFor('service_tickets', $modules),
            ['customers.view', 'products.view']
        );
        $serviceRole->syncPermissions($servicePermissions);

        // Viewer - dashboard.view + all *.view permissions
        $viewerRole = Role::firstOrCreate(['name' => 'Viewer', 'guard_name' => $guard]);
        $viewerPermissions = ['dashboard.view'];
        foreach ($modules as $module => $actions) {
            if (in_array('view', $actions)) {
                $viewerPermissions[] = "{$module}.view";
            }
        }
        $viewerRole->syncPermissions(array_unique($viewerPermissions));
    }

    /**
     * Get all permission names for a given module.
     */
    private function allActionsFor(string $module, array $modules): array
    {
        $permissions = [];
        if (isset($modules[$module])) {
            foreach ($modules[$module] as $action) {
                $permissions[] = "{$module}.{$action}";
            }
        }

        return $permissions;
    }
}
