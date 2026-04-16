<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = Admin::first()->id;
        $now = now();

        DB::table('warehouses')->insert([
            [
                'code' => 'WH-001',
                'name' => 'Main Warehouse',
                'address' => 'Plot No. 45, Industrial Estate, Ambattur, Chennai - 600058',
                'is_default' => true,
                'is_active' => true,
                'created_by' => $adminId,
                'updated_by' => null,
                'deleted_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'code' => 'WH-002',
                'name' => 'Ambattur Unit',
                'address' => 'No. 78, SIDCO Industrial Estate, Ambattur, Chennai - 600098',
                'is_default' => false,
                'is_active' => true,
                'created_by' => $adminId,
                'updated_by' => null,
                'deleted_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'code' => 'WH-003',
                'name' => 'Bangalore Branch',
                'address' => 'No. 12, Peenya Industrial Area, 2nd Phase, Bangalore - 560058',
                'is_default' => false,
                'is_active' => true,
                'created_by' => $adminId,
                'updated_by' => null,
                'deleted_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
        ]);
    }
}
