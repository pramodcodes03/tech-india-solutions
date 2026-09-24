<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * A permission of its own for the Helpdesk Report.
 *
 * It used to ride on documents_reports, which bundles it with payroll, leave
 * and CRM reports — so letting a department lead pull their own ticket numbers
 * meant handing them everything else too.
 */
return new class extends Migration
{
    private array $names = [
        // 'generate' is what the document render route checks, matching every
        // other document pack; 'configure' gates handing out departments.
        'helpdesk_reports.view',
        'helpdesk_reports.generate',
        'helpdesk_reports.configure',
    ];

    public function up(): void
    {
        foreach ($this->names as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'admin']);
        }

        foreach (['Super Admin', 'Admin', 'Business Admin'] as $roleName) {
            Role::where('name', $roleName)->where('guard_name', 'admin')->first()
                ?->givePermissionTo($this->names);
        }

        // HR and Service run the helpdesk day to day but do not hand out access.
        foreach (['HR Manager', 'Service'] as $roleName) {
            Role::where('name', $roleName)->where('guard_name', 'admin')->first()
                ?->givePermissionTo(['helpdesk_reports.view', 'helpdesk_reports.generate']);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('guard_name', 'admin')->whereIn('name', $this->names)->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
