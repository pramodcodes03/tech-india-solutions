<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Module D permissions.
 *
 * One row for the Documents hub itself plus one per pack, so a business can let
 * Accounts print the Sales & Finance pack without also handing them HR letters
 * or the payroll registers.
 *
 *   documents.view          open the Documents hub
 *   documents.configure     upload the signature / seal, edit the letterhead
 *   documents_payroll.*     Form 16, statutory register, salary register, …
 *   documents_attendance.*  muster roll, leave card, sanction slips, …
 *   documents_reports.*     the reports & modules pack
 *   documents_hr_letters.*  appointment, relieving, F&F, ID card, …
 *   documents_sales.*       sales order, GRN, statements, credit note, …
 */
return new class extends Migration
{
    private array $modules = [
        'documents' => ['view', 'configure'],
        'documents_payroll' => ['view', 'generate'],
        'documents_attendance' => ['view', 'generate'],
        'documents_reports' => ['view', 'generate'],
        'documents_hr_letters' => ['view', 'generate'],
        'documents_sales' => ['view', 'generate'],
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

        // HR gets the people-facing packs; the letterhead itself stays with Admin.
        Role::where('name', 'HR Manager')->where('guard_name', 'admin')->first()?->givePermissionTo([
            'documents.view',
            'documents_payroll.view', 'documents_payroll.generate',
            'documents_attendance.view', 'documents_attendance.generate',
            'documents_reports.view', 'documents_reports.generate',
            'documents_hr_letters.view', 'documents_hr_letters.generate',
        ]);

        // Accounts gets the money-facing pack.
        Role::where('name', 'Accounts')->where('guard_name', 'admin')->first()?->givePermissionTo([
            'documents.view',
            'documents_sales.view', 'documents_sales.generate',
            'documents_reports.view', 'documents_reports.generate',
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
