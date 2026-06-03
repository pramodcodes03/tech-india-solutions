<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Add per-dashboard view permissions so admins can toggle each specialised
 * analytics dashboard independently (Sales / Service / Inventory / Purchase
 * / Customer / Executive / HR / Asset). Previously every specialised
 * dashboard was gated on a borrowed permission from a related module
 * (e.g. leads.view for Sales dashboard), which meant turning off "Sales
 * Analytics" for a role required revoking the entire Leads module — too
 * coarse.
 *
 * Grants all 8 new permissions to existing Admin + Business Admin roles so
 * they don't lose access on deploy. Super Admin bypasses every gate via
 * Gate::before, so no row needed.
 */
return new class extends Migration
{
    public function up(): void
    {
        $guard = 'admin';

        $newPerms = [
            'analytics_sales.view',
            'analytics_service.view',
            'analytics_inventory.view',
            'analytics_purchase.view',
            'analytics_customer.view',
            'analytics_executive.view',
            'analytics_hr.view',
            'analytics_asset.view',
        ];

        $created = [];
        foreach ($newPerms as $name) {
            $created[] = Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => $guard,
            ]);
        }

        foreach (['Admin', 'Business Admin'] as $roleName) {
            $role = Role::where(['name' => $roleName, 'guard_name' => $guard])->first();
            if ($role) {
                $role->givePermissionTo($created);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $guard = 'admin';

        Permission::where('guard_name', $guard)
            ->whereIn('name', [
                'analytics_sales.view',
                'analytics_service.view',
                'analytics_inventory.view',
                'analytics_purchase.view',
                'analytics_customer.view',
                'analytics_executive.view',
                'analytics_hr.view',
                'analytics_asset.view',
            ])
            ->each(fn ($p) => $p->delete());

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
