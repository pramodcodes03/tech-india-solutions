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
            'businesses' => ['view', 'create', 'edit', 'delete'],
            'users' => ['view', 'create', 'edit', 'delete'],
            'roles' => ['view', 'create', 'edit', 'delete'],
            'customers' => ['view', 'create', 'edit', 'delete'],
            'leads' => ['view', 'create', 'edit', 'delete', 'convert'],
            'quotations' => ['view', 'create', 'edit', 'delete', 'export_pdf'],
            'proforma_invoices' => ['view', 'create', 'edit', 'delete', 'export_pdf'],
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
            'expense_categories' => ['view', 'create', 'edit', 'delete'],
            'expenses' => ['view', 'create', 'edit', 'delete', 'mark_paid'],

            // ── HR Module ────────────────────────────────────────────────
            'employees' => ['view', 'create', 'edit', 'delete', 'export'],
            'departments' => ['view', 'create', 'edit', 'delete'],
            'designations' => ['view', 'create', 'edit', 'delete'],
            'shifts' => ['view', 'create', 'edit', 'delete'],
            'holidays' => ['view', 'create', 'edit', 'delete'],
            'attendance' => ['view', 'create', 'edit', 'import'],
            'leaves' => ['view', 'create', 'approve', 'reject'],
            'leave_types' => ['view', 'create', 'edit', 'delete'],
            'payroll' => ['view', 'generate', 'approve', 'edit'],
            'salary_structures' => ['view', 'create', 'edit'],
            'warnings' => ['view', 'create', 'edit', 'delete'],
            'penalties' => ['view', 'create', 'edit', 'delete', 'reduce'],
            'feedback' => ['view'],
            'appraisals' => ['view', 'create', 'edit', 'finalize', 'acknowledge'],
            // Asset Management
            'asset_categories' => ['view', 'create', 'edit', 'delete'],
            'asset_locations' => ['view', 'create', 'edit', 'delete'],
            'asset_models' => ['view', 'create', 'edit', 'delete'],
            'assets' => ['view', 'create', 'edit', 'delete', 'assign', 'depreciate', 'maintenance', 'dispose', 'audit'],
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

        // Admin (per-business) - all permissions except users.delete, roles.delete,
        // and businesses.* which are Super-Admin-only.
        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => $guard]);
        $adminPermissions = array_filter($allPermissions, function ($perm) {
            return ! in_array($perm, ['users.delete', 'roles.delete'])
                && ! str_starts_with($perm, 'businesses.');
        });
        $adminRole->syncPermissions($adminPermissions);

        // Business Admin — alias for Admin, used when seeding initial admins
        // for each business. Same permission set; separate name keeps audit logs
        // and admin lists clear.
        $businessAdminRole = Role::firstOrCreate(['name' => 'Business Admin', 'guard_name' => $guard]);
        $businessAdminRole->syncPermissions($adminPermissions);

        // Sales
        $salesRole = Role::firstOrCreate(['name' => 'Sales', 'guard_name' => $guard]);
        $salesPermissions = array_merge(
            ['dashboard.view'],
            $this->allActionsFor('customers', $modules),
            $this->allActionsFor('leads', $modules),
            $this->allActionsFor('quotations', $modules),
            $this->allActionsFor('proforma_invoices', $modules),
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
            $this->allActionsFor('expenses', $modules),
            $this->allActionsFor('expense_categories', $modules),
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

        // HR Manager — full access to HR module + dashboard
        $hrRole = Role::firstOrCreate(['name' => 'HR Manager', 'guard_name' => $guard]);
        $hrPermissions = array_merge(
            ['dashboard.view'],
            $this->allActionsFor('employees', $modules),
            $this->allActionsFor('departments', $modules),
            $this->allActionsFor('designations', $modules),
            $this->allActionsFor('shifts', $modules),
            $this->allActionsFor('holidays', $modules),
            $this->allActionsFor('attendance', $modules),
            $this->allActionsFor('leaves', $modules),
            $this->allActionsFor('leave_types', $modules),
            $this->allActionsFor('payroll', $modules),
            $this->allActionsFor('salary_structures', $modules),
            $this->allActionsFor('warnings', $modules),
            $this->allActionsFor('penalties', $modules),
            $this->allActionsFor('feedback', $modules),
            $this->allActionsFor('appraisals', $modules),
        );
        $hrRole->syncPermissions($hrPermissions);

        // Give Admin role all HR permissions too
        $adminRole->givePermissionTo($hrPermissions);

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
