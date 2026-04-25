<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AssetCategory;
use App\Models\AssetLocation;
use App\Models\AssetMaintenanceLog;
use App\Models\AssetModel;
use App\Models\Employee;
use App\Models\PurchaseOrder;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AssetSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Admin::first();
        $adminId = $admin?->id;

        // ── Wipe existing asset data so the seeder is idempotent ──────────
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        AssetMaintenanceLog::truncate();
        AssetAssignment::truncate();
        Asset::truncate();
        AssetModel::truncate();
        AssetLocation::truncate();
        AssetCategory::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // ── 1. Categories (8) ─────────────────────────────────────────────
        $categories = collect([
            ['code' => 'LAP', 'name' => 'Laptop', 'method' => 'straight_line', 'life' => 4, 'sal' => 5],
            ['code' => 'DSK', 'name' => 'Desktop', 'method' => 'straight_line', 'life' => 5, 'sal' => 5],
            ['code' => 'MON', 'name' => 'Monitor', 'method' => 'straight_line', 'life' => 5, 'sal' => 5],
            ['code' => 'PRN', 'name' => 'Printer', 'method' => 'straight_line', 'life' => 5, 'sal' => 5],
            ['code' => 'SRV', 'name' => 'Server', 'method' => 'straight_line', 'life' => 6, 'sal' => 8],
            ['code' => 'NET', 'name' => 'Network Equipment', 'method' => 'straight_line', 'life' => 6, 'sal' => 5],
            ['code' => 'FUR', 'name' => 'Furniture', 'method' => 'straight_line', 'life' => 10, 'sal' => 10],
            ['code' => 'VEH', 'name' => 'Vehicle', 'method' => 'straight_line', 'life' => 8, 'sal' => 15],
        ])->mapWithKeys(function ($c) use ($adminId) {
            $cat = AssetCategory::create([
                'code' => $c['code'], 'name' => $c['name'],
                'default_depreciation_method' => $c['method'],
                'default_useful_life_years' => $c['life'],
                'default_salvage_percent' => $c['sal'],
                'status' => 'active',
                'created_by' => $adminId,
            ]);
            return [$c['code'] => $cat];
        });

        // ── 2. Locations (5) ──────────────────────────────────────────────
        $locations = collect([
            ['code' => 'HQ', 'name' => 'Head Office', 'type' => 'office', 'city' => 'Mumbai', 'state' => 'Maharashtra'],
            ['code' => 'BLR', 'name' => 'Bangalore Branch', 'type' => 'branch', 'city' => 'Bangalore', 'state' => 'Karnataka'],
            ['code' => 'DEL', 'name' => 'Delhi Branch', 'type' => 'branch', 'city' => 'New Delhi', 'state' => 'Delhi'],
            ['code' => 'WH1', 'name' => 'Mumbai Warehouse', 'type' => 'warehouse', 'city' => 'Mumbai', 'state' => 'Maharashtra'],
            ['code' => 'SITE-A', 'name' => 'Project Site A', 'type' => 'site', 'city' => 'Pune', 'state' => 'Maharashtra'],
        ])->mapWithKeys(function ($l) use ($adminId) {
            $loc = AssetLocation::create([
                'code' => $l['code'], 'name' => $l['name'], 'type' => $l['type'],
                'city' => $l['city'], 'state' => $l['state'],
                'manager_id' => Admin::inRandomOrder()->value('id'),
                'status' => 'active',
                'created_by' => $adminId,
            ]);
            return [$l['code'] => $loc];
        });

        // ── 3. Asset Models (15) ──────────────────────────────────────────
        $modelDefs = [
            ['code' => 'LAP-DL5420', 'cat' => 'LAP', 'name' => 'Latitude 5420', 'mfr' => 'Dell',  'mn' => '5420',  'specs' => ['CPU' => 'i7-1165G7', 'RAM' => '16GB', 'SSD' => '512GB', 'Screen' => '14"'], 'life' => 4, 'wmonths' => 36],
            ['code' => 'LAP-HPEB840', 'cat' => 'LAP', 'name' => 'EliteBook 840 G9', 'mfr' => 'HP', 'mn' => '840 G9', 'specs' => ['CPU' => 'i7-1255U', 'RAM' => '16GB', 'SSD' => '512GB', 'Screen' => '14"'], 'life' => 4, 'wmonths' => 36],
            ['code' => 'LAP-MBA-M2', 'cat' => 'LAP', 'name' => 'MacBook Air M2', 'mfr' => 'Apple','mn' => 'A2681', 'specs' => ['Chip' => 'Apple M2', 'RAM' => '16GB', 'SSD' => '512GB', 'Screen' => '13.6"'], 'life' => 5, 'wmonths' => 12],
            ['code' => 'DSK-DLOPT7', 'cat' => 'DSK', 'name' => 'OptiPlex 7090', 'mfr' => 'Dell',  'mn' => '7090',   'specs' => ['CPU' => 'i7-11700', 'RAM' => '16GB', 'SSD' => '512GB'], 'life' => 5, 'wmonths' => 36],
            ['code' => 'MON-DLU2722', 'cat' => 'MON', 'name' => 'UltraSharp U2722D', 'mfr' => 'Dell', 'mn' => 'U2722D', 'specs' => ['Size' => '27"', 'Resolution' => '2560x1440', 'Panel' => 'IPS'], 'life' => 5, 'wmonths' => 36],
            ['code' => 'MON-LG27', 'cat' => 'MON', 'name' => '27" 4K Monitor',     'mfr' => 'LG', 'mn' => '27UP850', 'specs' => ['Size' => '27"', 'Resolution' => '3840x2160', 'Panel' => 'IPS'], 'life' => 5, 'wmonths' => 24],
            ['code' => 'PRN-HPLJ4', 'cat' => 'PRN', 'name' => 'LaserJet Pro M404dn', 'mfr' => 'HP', 'mn' => 'M404dn', 'specs' => ['Type' => 'Mono Laser', 'Speed' => '38ppm', 'Duplex' => 'Yes'], 'life' => 5, 'wmonths' => 24],
            ['code' => 'PRN-CNMF', 'cat' => 'PRN', 'name' => 'imageCLASS MF445dw', 'mfr' => 'Canon', 'mn' => 'MF445dw', 'specs' => ['Type' => 'Mono Multifunction', 'Speed' => '40ppm'], 'life' => 5, 'wmonths' => 12],
            ['code' => 'SRV-DLR740', 'cat' => 'SRV', 'name' => 'PowerEdge R740', 'mfr' => 'Dell', 'mn' => 'R740',    'specs' => ['CPU' => '2× Xeon Gold 5218', 'RAM' => '128GB', 'Storage' => '2TB SSD RAID'], 'life' => 6, 'wmonths' => 60],
            ['code' => 'NET-CSC2960', 'cat' => 'NET', 'name' => 'Catalyst 2960 24-Port', 'mfr' => 'Cisco', 'mn' => 'WS-C2960', 'specs' => ['Ports' => '24', 'Speed' => '1Gbps', 'PoE' => 'No'], 'life' => 7, 'wmonths' => 60],
            ['code' => 'NET-UB-AP', 'cat' => 'NET', 'name' => 'UniFi AP AC Pro', 'mfr' => 'Ubiquiti', 'mn' => 'UAP-AC-PRO', 'specs' => ['Standard' => '802.11ac', 'Speed' => '1750 Mbps'], 'life' => 6, 'wmonths' => 24],
            ['code' => 'FUR-CHR-EXC', 'cat' => 'FUR', 'name' => 'Executive Mesh Chair', 'mfr' => 'Featherlite', 'mn' => 'EXC-2024', 'specs' => ['Material' => 'Mesh', 'Adjustable' => 'Yes'], 'life' => 10, 'wmonths' => 24],
            ['code' => 'FUR-DSK-WS', 'cat' => 'FUR', 'name' => 'Workstation Desk', 'mfr' => 'Godrej', 'mn' => 'WS-1500', 'specs' => ['Width' => '1500mm', 'Depth' => '600mm'], 'life' => 12, 'wmonths' => 12],
            ['code' => 'VEH-INVA', 'cat' => 'VEH', 'name' => 'Innova Crysta', 'mfr' => 'Toyota', 'mn' => '2.4 ZX', 'specs' => ['Fuel' => 'Diesel', 'Seats' => '7', 'Year' => '2024'], 'life' => 8, 'wmonths' => 36],
            ['code' => 'VEH-SWIFT', 'cat' => 'VEH', 'name' => 'Swift Dzire', 'mfr' => 'Maruti Suzuki', 'mn' => 'VXi', 'specs' => ['Fuel' => 'Petrol', 'Seats' => '5', 'Year' => '2024'], 'life' => 8, 'wmonths' => 36],
        ];

        $models = collect();
        foreach ($modelDefs as $md) {
            $cat = $categories[$md['cat']];
            $m = AssetModel::create([
                'code' => $md['code'], 'name' => $md['name'],
                'category_id' => $cat->id,
                'manufacturer' => $md['mfr'], 'model_number' => $md['mn'],
                'specifications' => $md['specs'],
                'default_depreciation_method' => $cat->default_depreciation_method,
                'default_useful_life_years' => $md['life'],
                'default_salvage_percent' => $cat->default_salvage_percent,
                'manufacturer_warranty_months' => $md['wmonths'],
                'status' => 'active',
                'created_by' => $adminId,
            ]);
            $models->push($m);
        }

        // ── 4. Assets (~60 units across all models) ───────────────────────
        $employees = Employee::whereIn('status', ['active', 'probation'])->get();
        $vendors   = Vendor::all();
        $pos       = PurchaseOrder::all();

        // realistic price ranges per category (in ₹)
        $priceRanges = [
            'LAP' => [60000, 150000], 'DSK' => [40000, 90000], 'MON' => [12000, 45000],
            'PRN' => [15000, 40000], 'SRV' => [350000, 800000], 'NET' => [8000, 60000],
            'FUR' => [5000, 25000], 'VEH' => [800000, 2500000],
        ];

        $statuses = ['in_storage', 'assigned', 'assigned', 'assigned', 'in_maintenance', 'retired']; // weighted
        $conditions = ['excellent', 'good', 'good', 'good', 'fair', 'poor'];

        $assetCount = 0;
        foreach ($models as $model) {
            $units = match ($model->category->code) {
                'SRV' => rand(1, 2),
                'VEH' => rand(1, 3),
                'NET' => rand(2, 5),
                default => rand(3, 6),
            };
            for ($i = 0; $i < $units; $i++) {
                $assetCount++;
                $catCode = $model->category->code;
                [$min, $max] = $priceRanges[$catCode];
                $cost = mt_rand($min, $max);
                $purchaseDate = Carbon::today()->subDays(rand(30, 1200));
                $salvageValue = round($cost * ($model->default_salvage_percent / 100), 2);
                $usefulLifeMonths = $model->default_useful_life_years * 12;
                $monthlyDep = ($cost - $salvageValue) / $usefulLifeMonths;
                $monthsElapsed = min($usefulLifeMonths, $purchaseDate->diffInMonths(now()));
                $accum = round($monthlyDep * $monthsElapsed, 2);
                $bookValue = max($salvageValue, round($cost - $accum, 2));

                $status = $statuses[array_rand($statuses)];
                $custodianId = null;
                if ($status === 'assigned' && $employees->isNotEmpty()) {
                    $custodianId = $employees->random()->id;
                }

                $asset = Asset::create([
                    'asset_code'       => $catCode.'-'.str_pad((string) $assetCount, 5, '0', STR_PAD_LEFT),
                    'name'             => $model->name.($units > 1 ? ' #'.($i + 1) : ''),
                    'serial_number'    => strtoupper(substr($model->manufacturer, 0, 2)).'-'.strtoupper(\Illuminate\Support\Str::random(8)),
                    'category_id'      => $model->category_id,
                    'asset_model_id'   => $model->id,
                    'location_id'      => $locations->random()->id,
                    'current_custodian_id' => $custodianId,
                    'vendor_id'        => $vendors->isNotEmpty() ? $vendors->random()->id : null,
                    'purchase_order_id'=> $pos->isNotEmpty() && rand(0, 1) ? $pos->random()->id : null,
                    'purchase_date'    => $purchaseDate,
                    'purchase_cost'    => $cost,
                    'salvage_value'    => $salvageValue,
                    'warranty_expiry_date'  => (clone $purchaseDate)->addMonths($model->manufacturer_warranty_months),
                    'insurance_expiry_date' => $catCode === 'VEH' ? (clone $purchaseDate)->addYear() : null,
                    'end_of_life_date'      => (clone $purchaseDate)->addYears($model->default_useful_life_years),
                    'depreciation_method'   => 'straight_line',
                    'useful_life_years'     => $model->default_useful_life_years,
                    'depreciation_start_date' => $purchaseDate,
                    'last_depreciation_posted_on' => $monthsElapsed > 0 ? Carbon::today()->subMonth()->endOfMonth() : null,
                    'accumulated_depreciation' => $accum,
                    'current_book_value'       => $bookValue,
                    'status'           => $status,
                    'condition_rating' => $conditions[array_rand($conditions)],
                    'is_lost'          => rand(1, 100) === 1,
                    'created_by'       => $adminId,
                ]);

                // ── 5. If assigned, log the assignment ───────────────────
                if ($custodianId) {
                    $assignedAt = (clone $purchaseDate)->addDays(rand(1, 30));
                    AssetAssignment::create([
                        'assignment_code'    => 'ASN-'.str_pad((string) $assetCount, 5, '0', STR_PAD_LEFT),
                        'asset_id'           => $asset->id,
                        'employee_id'        => $custodianId,
                        'from_location_id'   => $asset->location_id,
                        'to_location_id'     => $asset->location_id,
                        'assigned_at'        => $assignedAt,
                        'action_type'        => 'assign',
                        'condition_at_assign'=> 'good',
                        'notes'              => 'Issued to employee for daily use.',
                        'issued_by'          => $adminId,
                    ]);

                    // Sometimes a previous, returned assignment too (for history depth)
                    if (rand(1, 4) === 1 && $employees->count() > 1) {
                        $priorEmp = $employees->where('id', '!=', $custodianId)->random();
                        $priorAt = (clone $purchaseDate)->addDays(rand(1, 5));
                        $priorReturn = (clone $assignedAt)->subDays(rand(1, 7));
                        AssetAssignment::create([
                            'assignment_code'    => 'ASN-'.str_pad((string) $assetCount, 5, '0', STR_PAD_LEFT).'-P',
                            'asset_id'           => $asset->id,
                            'employee_id'        => $priorEmp->id,
                            'from_location_id'   => $asset->location_id,
                            'to_location_id'     => $asset->location_id,
                            'assigned_at'        => $priorAt,
                            'returned_at'        => $priorReturn,
                            'action_type'        => 'assign',
                            'condition_at_assign'=> 'good',
                            'condition_at_return'=> 'good',
                            'return_notes'       => 'Returned: employee reassigned.',
                            'issued_by'          => $adminId,
                            'received_by'        => $adminId,
                        ]);
                    }
                }

                // ── 6. Maintenance logs (some assets get 0–3 entries) ────
                $maintCount = rand(0, 3);
                for ($k = 0; $k < $maintCount; $k++) {
                    $type = ['corrective', 'preventive', 'inspection', 'preventive'][array_rand(['corrective', 'preventive', 'inspection', 'preventive'])];
                    $performedAt = (clone $purchaseDate)->addDays(rand(60, max(60, $purchaseDate->diffInDays(now()))));
                    if ($performedAt->isFuture()) continue;
                    $partsCost  = $type === 'corrective' ? rand(500, 8000) : rand(0, 1500);
                    $labourCost = rand(500, 3000);
                    $log = AssetMaintenanceLog::create([
                        'log_code'  => strtoupper(substr($type, 0, 2)).'-'.str_pad((string) ($assetCount * 10 + $k), 6, '0', STR_PAD_LEFT),
                        'asset_id'  => $asset->id,
                        'type'      => $type,
                        'scheduled_date' => $type === 'preventive' ? $performedAt->copy()->subDays(rand(0, 7)) : null,
                        'performed_date' => $performedAt,
                        'performed_by_employee_id' => $employees->isNotEmpty() && rand(0, 1) ? $employees->random()->id : null,
                        'performed_by'   => rand(0, 1) ? null : 'External Technician',
                        'vendor_name'    => $type === 'corrective' && rand(0, 1) ? 'AMC Services Pvt Ltd' : null,
                        'parts_cost'     => $partsCost,
                        'labour_cost'    => $labourCost,
                        'total_cost'     => $partsCost + $labourCost,
                        'downtime_hours' => $type === 'corrective' ? rand(2, 16) : rand(0, 4),
                        'description'   => match ($type) {
                            'corrective'  => 'Repair required after fault report from custodian.',
                            'preventive'  => 'Scheduled preventive maintenance per service plan.',
                            'inspection'  => 'Routine inspection and condition assessment.',
                            default       => 'Audit verification and physical check.',
                        },
                        'parts_used'      => $type === 'corrective' ? "Replacement part ×1\nCleaning kit ×1" : null,
                        'resolution_notes'=> 'Restored to operational condition.',
                        'status'          => 'completed',
                        'created_by'      => $adminId,
                    ]);
                }
            }
        }

        $this->command->info("Seeded: ".AssetCategory::count()." categories, ".AssetLocation::count()." locations, ".AssetModel::count()." models, ".Asset::count()." assets, ".AssetAssignment::count()." assignments, ".AssetMaintenanceLog::count()." maintenance logs.");
    }
}
