<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\RequisitionCategory;
use Illuminate\Database\Seeder;

class RequisitionCategorySeeder extends Seeder
{
    /**
     * Seed the default Requisition Category rows for every existing business.
     * Idempotent — keyed by (business_id, key) so re-running won't duplicate.
     *
     * Newly-created businesses are seeded by {@see seedForBusiness()}, called
     * from BusinessService::create() so a fresh tenant has the same defaults.
     */
    public function run(): void
    {
        Business::query()->each(fn (Business $b) => self::seedForBusiness($b->id));
    }

    public static function seedForBusiness(int $businessId): void
    {
        foreach (self::defaults() as $sort => $row) {
            RequisitionCategory::withoutGlobalScopes()->updateOrCreate(
                ['business_id' => $businessId, 'key' => $row['key']],
                [
                    'label'      => $row['label'],
                    'sort_order' => $sort,
                    'is_active'  => true,
                    'is_system'  => true,
                ],
            );
        }
    }

    /**
     * Order matters — the index becomes sort_order. Slugs match the legacy
     * Requisition::CATEGORIES keys so existing requisitions keep their label.
     */
    public static function defaults(): array
    {
        return [
            ['key' => 'furniture',    'label' => 'Furniture'],
            ['key' => 'chairs',       'label' => 'Chairs'],
            ['key' => 'systems',      'label' => 'Systems'],
            ['key' => 'it_equipment', 'label' => 'IT Equipment'],
            ['key' => 'stationery',   'label' => 'Stationery'],
            ['key' => 'other',        'label' => 'Other'],
        ];
    }
}
