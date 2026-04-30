<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Business;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Super Admin — no business; can manage all businesses.
            $super = Admin::firstOrCreate(
                ['email' => 'super@altechnics.com'],
                [
                    'name' => 'Super Admin',
                    'password' => Hash::make('Admin@12345'),
                    'phone' => '+91 98401 00000',
                    'status' => 'active',
                    'business_id' => null,
                ],
            );
            if (! $super->hasRole('Super Admin')) {
                $super->assignRole('Super Admin');
            }

            // One Business Admin per business.
            foreach (Business::all() as $business) {
                $admin = Admin::firstOrCreate(
                    ['email' => 'admin@'.$business->slug.'.test'],
                    [
                        'name' => $business->name.' Admin',
                        'password' => Hash::make('Admin@12345'),
                        'phone' => $business->phone,
                        'status' => 'active',
                        'business_id' => $business->id,
                    ],
                );
                if (! $admin->hasRole('Business Admin')) {
                    $admin->assignRole('Business Admin');
                }
            }
        });
    }
}
