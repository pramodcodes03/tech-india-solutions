<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * `helpdesk_reports.export` — the right to take helpdesk data out of the system
 * as a PDF or spreadsheet.
 *
 * Separate from `.view` and `.generate` on purpose: reading ticket numbers on
 * screen and walking out with a file of every raiser's name and employee code
 * are different levels of trust. Combined with the per-admin department
 * restriction, a department lead can be allowed to export their own
 * department's tickets and nobody else's.
 */
return new class extends Migration
{
    public function up(): void
    {
        Permission::firstOrCreate(['name' => 'helpdesk_reports.export', 'guard_name' => 'admin']);

        foreach (['Super Admin', 'Admin', 'Business Admin', 'HR Manager'] as $roleName) {
            Role::where('name', $roleName)->where('guard_name', 'admin')->first()
                ?->givePermissionTo('helpdesk_reports.export');
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('guard_name', 'admin')->where('name', 'helpdesk_reports.export')->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
