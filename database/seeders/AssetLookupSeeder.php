<?php

namespace Database\Seeders;

use App\Models\AssetMaintenanceType;
use App\Models\AssetStatus;
use App\Models\Business;
use Illuminate\Database\Seeder;

class AssetLookupSeeder extends Seeder
{
    /**
     * Seed default Asset Status + Maintenance Type rows for every existing
     * business. Idempotent — uses updateOrCreate keyed by (business_id, key)
     * so re-running won't duplicate.
     *
     * Newly-created businesses are seeded by {@see seedForBusiness()}, called
     * from BusinessService::create() so a fresh tenant has the same defaults
     * day one.
     */
    public function run(): void
    {
        Business::query()->each(fn (Business $b) => self::seedForBusiness($b->id));
    }

    public static function seedForBusiness(int $businessId): void
    {
        foreach (self::statusDefaults() as $sort => $row) {
            AssetStatus::withoutGlobalScopes()->updateOrCreate(
                ['business_id' => $businessId, 'key' => $row['key']],
                [
                    'label'      => $row['label'],
                    'color'      => $row['color'] ?? null,
                    'sort_order' => $sort,
                    'is_active'  => true,
                    'is_system'  => true,
                ],
            );
        }

        foreach (self::maintenanceTypeDefaults() as $sort => $row) {
            AssetMaintenanceType::withoutGlobalScopes()->updateOrCreate(
                ['business_id' => $businessId, 'key' => $row['key']],
                [
                    'label'      => $row['label'],
                    'color'      => $row['color'] ?? null,
                    'sort_order' => $sort,
                    'is_active'  => true,
                    'is_system'  => true,
                ],
            );
        }
    }

    /**
     * Order matters — the index determines sort_order on the dropdown.
     * Existing slugs ('draft', 'in_storage', etc.) preserve compatibility
     * with assets already in the DB. The new ones are appended.
     */
    public static function statusDefaults(): array
    {
        return [
            ['key' => 'draft',                 'label' => 'Draft',                  'color' => 'secondary'],
            ['key' => 'in_storage',            'label' => 'In Storage',             'color' => 'info'],
            ['key' => 'assigned',              'label' => 'Assigned',               'color' => 'success'],
            ['key' => 'in_maintenance',        'label' => 'In Maintenance',         'color' => 'warning'],
            ['key' => 'retired',               'label' => 'Retired',                'color' => 'danger'],
            ['key' => 'disposed',              'label' => 'Disposed',               'color' => 'danger'],
            ['key' => 'backup_system',         'label' => 'Backup System',          'color' => 'info'],
            ['key' => 'discard_assets',        'label' => 'Discard Assets',         'color' => 'warning'],
            ['key' => 'discarded',             'label' => 'Discarded',              'color' => 'danger'],
            ['key' => 'faulty_under_repair',   'label' => 'Faulty / Under Repair',  'color' => 'warning'],
            ['key' => 'non_repairable',        'label' => 'Non Repairable',         'color' => 'danger'],
            ['key' => 'pending',               'label' => 'Pending',                'color' => 'secondary'],
            ['key' => 'sent_for_repair',       'label' => 'Sent For Repair',        'color' => 'warning'],
            ['key' => 'sold',                  'label' => 'Sold',                   'color' => 'secondary'],
            ['key' => 'sold_out_to_advisors',  'label' => 'Sold Out To Advisors',   'color' => 'secondary'],
        ];
    }

    public static function maintenanceTypeDefaults(): array
    {
        return [
            ['key' => 'corrective',            'label' => 'Corrective',             'color' => 'warning'],
            ['key' => 'preventive',            'label' => 'Preventive',             'color' => 'info'],
            ['key' => 'inspection',            'label' => 'Inspection',             'color' => 'secondary'],
            ['key' => 'audit',                 'label' => 'Audit',                  'color' => 'secondary'],
            ['key' => 'maintenance',           'label' => 'Maintenance',            'color' => 'info'],
            ['key' => 'repair',                'label' => 'Repair',                 'color' => 'warning'],
            ['key' => 'upgrade',               'label' => 'Upgrade',                'color' => 'success'],
            ['key' => 'configuration_change',  'label' => 'Configuration Change',   'color' => 'info'],
        ];
    }
}
