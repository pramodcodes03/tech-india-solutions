<?php

namespace Tests\Traits;

use App\Models\Admin;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\PermissionRegistrar;

trait CreatesAdminUsers
{
    protected function seedPermissions(): void
    {
        $this->seed(RolePermissionSeeder::class);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    protected function createSuperAdmin(): Admin
    {
        $admin = Admin::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@test.com',
            'password' => bcrypt('password'),
            'phone' => '9876543210',
            'status' => 'active',
        ]);
        $admin->assignRole('Super Admin');

        return $admin;
    }

    protected function createAdminWithRole(string $roleName, ?string $email = null): Admin
    {
        $admin = Admin::create([
            'name' => $roleName.' User',
            'email' => $email ?? strtolower(str_replace(' ', '', $roleName)).'@test.com',
            'password' => bcrypt('password'),
            'phone' => '9876543'.rand(100, 999),
            'status' => 'active',
        ]);
        $admin->assignRole($roleName);

        return $admin;
    }
}
