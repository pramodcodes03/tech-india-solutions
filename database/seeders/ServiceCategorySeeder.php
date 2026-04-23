<?php

namespace Database\Seeders;

use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

class ServiceCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Electrician', 'icon' => '⚡', 'color' => '#f59e0b', 'description' => 'Electrical work — wiring, fixtures, repairs'],
            ['name' => 'Plumber', 'icon' => '🔧', 'color' => '#3b82f6', 'description' => 'Plumbing — leaks, fittings, drain cleaning'],
            ['name' => 'Carpenter', 'icon' => '🪚', 'color' => '#92400e', 'description' => 'Carpentry — furniture, doors, woodwork'],
            ['name' => 'AC Technician', 'icon' => '❄️', 'color' => '#06b6d4', 'description' => 'AC installation, servicing & repair'],
            ['name' => 'Painter', 'icon' => '🎨', 'color' => '#ec4899', 'description' => 'Interior & exterior painting'],
            ['name' => 'Mason', 'icon' => '🧱', 'color' => '#78350f', 'description' => 'Civil & masonry work'],
            ['name' => 'Housekeeping', 'icon' => '🧹', 'color' => '#10b981', 'description' => 'Cleaning & housekeeping services'],
            ['name' => 'Pest Control', 'icon' => '🐜', 'color' => '#dc2626', 'description' => 'Pest control treatment'],
            ['name' => 'Appliance Repair', 'icon' => '🔌', 'color' => '#8b5cf6', 'description' => 'Repair of home appliances'],
            ['name' => 'IT Support', 'icon' => '💻', 'color' => '#0ea5e9', 'description' => 'Computer & network support'],
            ['name' => 'General Maintenance', 'icon' => '🛠️', 'color' => '#64748b', 'description' => 'Miscellaneous maintenance work'],
        ];

        foreach ($categories as $i => $c) {
            ServiceCategory::firstOrCreate(
                ['name' => $c['name']],
                $c + ['sort_order' => $i, 'status' => 'active']
            );
        }
    }
}
