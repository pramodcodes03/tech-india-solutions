<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $admins = [
                [
                    'name' => 'Super Admin',
                    'email' => 'admin@altechnics.com',
                    'password' => Hash::make('Admin@12345'),
                    'phone' => '+91 98401 12345',
                    'status' => 'active',
                    'role' => 'Super Admin',
                ],
                [
                    'name' => 'Rajesh Kumar',
                    'email' => 'rajesh@altechnics.com',
                    'password' => Hash::make('Admin@12345'),
                    'phone' => '+91 98401 23456',
                    'status' => 'active',
                    'role' => 'Admin',
                ],
                [
                    'name' => 'Priya Sharma',
                    'email' => 'priya@altechnics.com',
                    'password' => Hash::make('Admin@12345'),
                    'phone' => '+91 98401 34567',
                    'status' => 'active',
                    'role' => 'Sales',
                ],
                [
                    'name' => 'Suresh Nair',
                    'email' => 'suresh@altechnics.com',
                    'password' => Hash::make('Admin@12345'),
                    'phone' => '+91 98401 45678',
                    'status' => 'active',
                    'role' => 'Inventory',
                ],
                [
                    'name' => 'Lakshmi Devi',
                    'email' => 'lakshmi@altechnics.com',
                    'password' => Hash::make('Admin@12345'),
                    'phone' => '+91 98401 56789',
                    'status' => 'active',
                    'role' => 'Accounts',
                ],
                [
                    'name' => 'Mohammed Ali',
                    'email' => 'mohammed@altechnics.com',
                    'password' => Hash::make('Admin@12345'),
                    'phone' => '+91 98401 67890',
                    'status' => 'active',
                    'role' => 'Service',
                ],
                [
                    'name' => 'Anita Verma',
                    'email' => 'anita@altechnics.com',
                    'password' => Hash::make('Admin@12345'),
                    'phone' => '+91 98401 78901',
                    'status' => 'active',
                    'role' => 'Viewer',
                ],
            ];

            foreach ($admins as $data) {
                $role = $data['role'];
                unset($data['role']);

                $admin = Admin::create($data);
                $admin->assignRole($role);
            }
        });
    }
}
