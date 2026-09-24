<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Module A permissions, shaped to the proposal's four-role matrix:
 *
 *   Admin     create KRA/KPI, assign goals, final approval, full reports
 *   HR        create KRA/KPI, assign goals, HR review, full reports
 *   Manager   assign goals, manager review, limited reports (own team)
 *   Employee  self assessment, own reports only  (employee guard — gated by
 *             ownership in the portal, not by these admin-guard permissions)
 *
 * "Manager" here is the Department Head working in the employee portal, so the
 * admin-guard rows below cover Admin and HR; a manager-role admin account can
 * be given performance_reviews.manager_review on its own.
 */
return new class extends Migration
{
    private array $modules = [
        // Cycles, bands, bell curve and the scoring configuration.
        'performance' => ['view', 'configure'],
        'performance_kra' => ['view', 'create', 'edit', 'delete'],
        'performance_kpi' => ['view', 'create', 'edit', 'delete'],
        'performance_goals' => ['view', 'assign', 'bulk_assign', 'import', 'delete'],
        'performance_reviews' => ['view', 'manager_review', 'hr_review', 'finalize', 'send_back'],
        'performance_rewards' => ['view', 'manage'],
        'performance_reports' => ['view', 'export'],
        'analytics_performance' => ['view'],
    ];

    public function up(): void
    {
        $guard = 'admin';

        $names = [];
        foreach ($this->modules as $module => $actions) {
            foreach ($actions as $action) {
                $names[] = "{$module}.{$action}";
                Permission::firstOrCreate(['name' => "{$module}.{$action}", 'guard_name' => $guard]);
            }
        }

        foreach (['Super Admin', 'Admin', 'Business Admin', 'HR Manager'] as $roleName) {
            Role::where('name', $roleName)->where('guard_name', $guard)->first()?->givePermissionTo($names);
        }

        // Viewer mirrors its existing "every *.view" rule, minus the analytics
        // dashboards which it is deliberately kept out of.
        Role::where('name', 'Viewer')->where('guard_name', $guard)->first()?->givePermissionTo(
            array_filter($names, fn ($n) => str_ends_with($n, '.view') && ! str_starts_with($n, 'analytics_')),
        );

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
