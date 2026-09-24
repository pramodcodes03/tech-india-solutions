<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Payslip deletion, split into two permissions so the "already paid" safeguard
 * is a real permission boundary rather than a checkbox anyone can tick:
 *
 *   payroll.delete       remove a generated (unpaid) payslip — bulk or one
 *                        employee at a time. HR needs this to clear a run that
 *                        was generated on wrong data and start again.
 *   payroll.delete_paid  the explicit override that also removes payslips
 *                        already marked Paid. Admin / Super Admin only.
 */
return new class extends Migration
{
    private array $names = ['payroll.delete', 'payroll.delete_paid'];

    public function up(): void
    {
        foreach ($this->names as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'admin']);
        }

        foreach (['Super Admin', 'Admin', 'Business Admin'] as $roleName) {
            Role::where('name', $roleName)->where('guard_name', 'admin')->first()
                ?->givePermissionTo($this->names);
        }

        // HR clears bad runs; removing a payslip that has already been paid out
        // stays with Admin.
        Role::where('name', 'HR Manager')->where('guard_name', 'admin')->first()
            ?->givePermissionTo('payroll.delete');

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('guard_name', 'admin')->whereIn('name', $this->names)->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
