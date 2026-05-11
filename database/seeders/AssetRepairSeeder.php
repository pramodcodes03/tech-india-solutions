<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Asset;
use App\Models\AssetRepairActivityLog;
use App\Models\AssetRepairRequest;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds realistic Indian demo data for the Asset Repair Approval module.
 *
 * Creates:
 *  - Non-repairable flags on a handful of assets
 *  - 20+ repair requests with full status journeys
 *  - Complete activity trail per request
 *  - Costing approval tickets on approved requests
 *
 * Run standalone : php artisan db:seed --class=AssetRepairSeeder
 * Idempotent     : truncates repair tables before seeding
 */
class AssetRepairSeeder extends Seeder
{
    // ── Indian repair vendors ────────────────────────────────────────────
    private array $vendors = [
        'Ravi Electronics Service Centre, Pune',
        'Kumar IT Solutions, Bengaluru',
        'Sharma Vehicle Workshop, Mumbai',
        'Tech Care India, Hyderabad',
        'National Computer Services, Delhi',
        'Vijay Printers & Services, Chennai',
        'Mehta Electrical Works, Ahmedabad',
        'Raj Network Solutions, Kolkata',
        'Om Sai Repair Centre, Nagpur',
        'Bharat Office Equipment Services, Jaipur',
        'Suresh Auto Care, Noida',
        'Ganesh Server Solutions, Pune',
        'Krishna IT Hub, Bengaluru',
        'Patel Hardware Repairs, Surat',
        'Rao Electronics, Coimbatore',
    ];

    // ── Realistic Indian repair descriptions per asset category ─────────
    private array $descriptions = [
        'LAP' => [
            'Laptop screen flickering intermittently — backlight failure suspected. Needs panel replacement.',
            'Keyboard keys (J, K, L) not responding. Spill damage. Require keyboard replacement and cleaning.',
            'Battery not charging beyond 20%. Swollen battery identified. Needs immediate replacement.',
            'Laptop overheating — thermal paste dried out and cooling fan making grinding noise. Full thermal service required.',
            'Laptop dropped from desk — hinge broken, screen cracked on lower-right corner. Physical repair needed.',
            'Power adapter port loose inside chassis. Device not charging without holding cable at angle.',
        ],
        'DSK' => [
            'Desktop not booting — POST failure. Likely SMPS fault. Requires PSU replacement.',
            'Random blue screen of death (BSOD) on Windows. RAM slot possibly damaged. Diagnosis and repair needed.',
            'CPU fan failed — system shuts down after 5 minutes. Fan unit and thermal service required.',
        ],
        'MON' => [
            'Monitor has vertical pink lines across display. Panel defect. Replacement required.',
            'Monitor power button not working — stuck in standby. Internal board repair needed.',
            'Display shows black blotches on left side — liquid crystal leak. Panel replacement required.',
        ],
        'PRN' => [
            'Printer paper jam error persistent — feed roller worn out. Internal cleaning and roller replacement needed.',
            'Printer showing offline in network — NIC card may have failed. Diagnosis and repair required.',
            'Toner cartridge slot broken — cartridge falling out mid-print. Mechanical repair needed.',
        ],
        'SRV' => [
            'Server RAID controller throwing alerts — disk 3 failed. Controller diagnostic and disk replacement needed.',
            'Server drawing excessive power — likely PSU degradation. Redundant PSU replacement required before failure.',
        ],
        'NET' => [
            'Network switch — 4 ports dead, device rebooting randomly. Board-level repair or replacement required.',
            'Wi-Fi access point signal very weak after power surge. RF module damage suspected.',
        ],
        'FUR' => [
            'Executive chair hydraulic lift failed — chair sinking to lowest position. Gas cylinder replacement needed.',
            'Workstation desk drawer rail broken — drawer not closing properly. Rail and locking mechanism repair.',
        ],
        'VEH' => [
            'Company vehicle AC not cooling — compressor seized. Full AC service and compressor replacement needed.',
            'Vehicle brake pads worn beyond limit — grinding noise on braking. Urgent brake pad and disc replacement.',
            'Engine oil leak traced to gasket failure. Full gasket set replacement required. Estimated 2-day service.',
            'Front bumper damage after minor collision in parking lot. Bumper repair/replacement and panel painting needed.',
        ],
    ];

    private int $requestCounter = 1;

    // ─────────────────────────────────────────────────────────────────────

    public function run(): void
    {
        // ── Wipe existing repair data (idempotent) ───────────────────────
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        AssetRepairActivityLog::truncate();
        AssetRepairRequest::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Also reset is_non_repairable on all assets so we start clean
        Asset::withoutGlobalScopes()->update(['is_non_repairable' => false]);

        $admin = Admin::first();
        if (! $admin) {
            $this->command->warn('No admin found — skipping AssetRepairSeeder.');
            return;
        }

        $allAdmins = Admin::all();
        $assets    = Asset::with('category')->get();

        if ($assets->isEmpty()) {
            $this->command->warn('No assets found — run AssetSeeder first, then re-run this seeder.');
            return;
        }

        $businessId = $assets->first()->business_id;

        // ── 1. Mark some assets as non-repairable ───────────────────────
        $nonRepairableCount = $this->markNonRepairableAssets($assets, $admin);

        // ── 2. Seed repair requests ──────────────────────────────────────
        $repairableAssets = $assets->where('is_non_repairable', false)
            ->whereNotIn('status', ['disposed', 'retired'])
            ->values();

        if ($repairableAssets->isEmpty()) {
            $this->command->warn('No repairable assets available for seeding repair requests.');
            return;
        }

        $created = $this->seedRepairRequests($repairableAssets, $allAdmins, $businessId);

        $this->command->info(sprintf(
            'Asset Repair Seeder: %d non-repairable assets flagged | %d repair requests created | %d activity logs written.',
            $nonRepairableCount,
            AssetRepairRequest::withoutGlobalScopes()->count(),
            AssetRepairActivityLog::withoutGlobalScopes()->count()
        ));
    }

    // ─────────────────────────────────────────────────────────────────────
    // NON-REPAIRABLE ASSETS
    // ─────────────────────────────────────────────────────────────────────

    private function markNonRepairableAssets($assets, Admin $admin): int
    {
        // Pick ~10% of assets (min 3, max 8) and mark as non-repairable
        $count = max(3, min(8, (int) ($assets->count() * 0.10)));
        $picked = $assets->random($count);

        foreach ($picked as $asset) {
            $asset->update(['is_non_repairable' => true, 'updated_by' => $admin->id]);
        }

        return $count;
    }

    // ─────────────────────────────────────────────────────────────────────
    // REPAIR REQUESTS — 24 realistic scenarios
    // ─────────────────────────────────────────────────────────────────────

    private function seedRepairRequests($repairableAssets, $allAdmins, int $businessId): void
    {
        $now = Carbon::now();

        /*
         * Each definition: [daysAgo, status, hasCostApproval, costStatus]
         *
         * status values   : pending | approved | rejected |
         *                   cost_approval_pending | cost_approved | cost_rejected
         * hasCostApproval : true = a costing ticket was raised after approval
         * costStatus      : pending | approved | rejected (only if hasCostApproval)
         */
        $scenarios = [
            // ── Fully resolved (old, cost approved) ─────────────────────
            ['days' => -320, 'status' => 'cost_approved',         'costStatus' => 'approved'],
            ['days' => -298, 'status' => 'cost_approved',         'costStatus' => 'approved'],
            ['days' => -275, 'status' => 'cost_approved',         'costStatus' => 'approved'],
            ['days' => -255, 'status' => 'cost_approved',         'costStatus' => 'approved'],
            ['days' => -240, 'status' => 'cost_approved',         'costStatus' => 'approved'],
            // ── Approved, cost rejected ──────────────────────────────────
            ['days' => -220, 'status' => 'cost_rejected',         'costStatus' => 'rejected'],
            ['days' => -200, 'status' => 'cost_rejected',         'costStatus' => 'rejected'],
            // ── Approved, awaiting cost approval ────────────────────────
            ['days' => -60,  'status' => 'cost_approval_pending', 'costStatus' => 'pending'],
            ['days' => -45,  'status' => 'cost_approval_pending', 'costStatus' => 'pending'],
            ['days' => -30,  'status' => 'cost_approval_pending', 'costStatus' => 'pending'],
            // ── Approved (no costing raised yet) ────────────────────────
            ['days' => -180, 'status' => 'approved',              'costStatus' => null],
            ['days' => -150, 'status' => 'approved',              'costStatus' => null],
            ['days' => -120, 'status' => 'approved',              'costStatus' => null],
            ['days' => -90,  'status' => 'approved',              'costStatus' => null],
            ['days' => -55,  'status' => 'approved',              'costStatus' => null],
            // ── Rejected ────────────────────────────────────────────────
            ['days' => -190, 'status' => 'rejected',              'costStatus' => null],
            ['days' => -140, 'status' => 'rejected',              'costStatus' => null],
            ['days' => -80,  'status' => 'rejected',              'costStatus' => null],
            ['days' => -35,  'status' => 'rejected',              'costStatus' => null],
            // ── Pending (fresh requests) ─────────────────────────────────
            ['days' => -20,  'status' => 'pending',               'costStatus' => null],
            ['days' => -14,  'status' => 'pending',               'costStatus' => null],
            ['days' => -10,  'status' => 'pending',               'costStatus' => null],
            ['days' => -5,   'status' => 'pending',               'costStatus' => null],
            ['days' => -2,   'status' => 'pending',               'costStatus' => null],
        ];

        foreach ($scenarios as $s) {
            $asset     = $repairableAssets->random();
            $catCode   = $asset->category?->code ?? 'LAP';
            $requester = $allAdmins->random();
            $approver  = $allAdmins->where('id', '!=', $requester->id)->random() ?? $allAdmins->first();

            $raisedAt   = $now->copy()->addDays($s['days']);
            $actionedAt = $raisedAt->copy()->addDays(rand(1, 4));
            $deliveryDate = $raisedAt->copy()->addDays(rand(10, 45));

            $estimatedCost = in_array($catCode, ['SRV', 'VEH'])
                ? rand(15000, 120000)
                : rand(2000, 30000);

            $descList = $this->descriptions[$catCode] ?? $this->descriptions['LAP'];
            $description = $descList[array_rand($descList)];
            $vendor = $this->vendors[array_rand($this->vendors)];

            $code = 'ARR' . str_pad($this->requestCounter, 5, '0', STR_PAD_LEFT);
            $this->requestCounter++;

            // ── Build the repair request row ─────────────────────────────
            $repairData = [
                'business_id'          => $businessId,
                'request_code'         => $code,
                'asset_id'             => $asset->id,
                'asset_type'           => $asset->category?->name ?? 'General Equipment',
                'vendor_name'          => $vendor,
                'repair_delivery_date' => $deliveryDate->toDateString(),
                'description'          => $description,
                'estimated_cost'       => rand(0, 5) > 0 ? $estimatedCost : null, // ~80% have estimate
                'status'               => $s['status'],
                'requested_by'         => $requester->id,
                'created_at'           => $raisedAt->toDateTimeString(),
                'updated_at'           => $raisedAt->toDateTimeString(),
            ];

            // Approval fields
            if ($s['status'] !== 'pending') {
                $repairData['approved_by']      = $approver->id;
                $repairData['approved_at']      = $actionedAt->toDateTimeString();
                $repairData['approval_remarks'] = $this->approvalRemark($s['status'], $approver->name);
            }

            // Costing fields
            if ($s['costStatus'] !== null) {
                $costRaisedAt  = $actionedAt->copy()->addDays(rand(3, 10));
                $costActionedAt = $costRaisedAt->copy()->addDays(rand(1, 3));
                $actualCost = (int)($estimatedCost * (rand(85, 130) / 100));
                $costApprover = $allAdmins->where('id', '!=', $requester->id)->random() ?? $allAdmins->first();

                $repairData['costing_requested_amount'] = $actualCost;
                $repairData['costing_description']      = $this->costingDescription($catCode, $actualCost);
                $repairData['costing_status']           = $s['costStatus'];

                if ($s['costStatus'] !== 'pending') {
                    $repairData['costing_approved_by'] = $costApprover->id;
                    $repairData['costing_approved_at'] = $costActionedAt->toDateTimeString();
                    $repairData['costing_remarks']     = $this->costingRemark($s['costStatus'], $costApprover->name);
                }

                $repairData['updated_at'] = $costActionedAt->toDateTimeString();
            }

            $repair = AssetRepairRequest::create($repairData);

            // ── Write the activity trail ─────────────────────────────────
            $this->writeActivityTrail($repair, $requester, $approver, $s, $raisedAt, $actionedAt, $businessId);
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // ACTIVITY TRAIL
    // ─────────────────────────────────────────────────────────────────────

    private function writeActivityTrail(
        AssetRepairRequest $repair,
        Admin $requester,
        Admin $approver,
        array $scenario,
        Carbon $raisedAt,
        Carbon $actionedAt,
        int $businessId
    ): void {
        $logs = [];

        // Event 1: Request raised
        $logs[] = [
            'business_id'             => $businessId,
            'asset_repair_request_id' => $repair->id,
            'performed_by'            => $requester->id,
            'performed_by_name'       => $requester->name,
            'event'                   => 'request_raised',
            'remarks'                 => null,
            'status_snapshot'         => 'pending',
            'costing_status_snapshot' => null,
            'performed_at'            => $raisedAt->toDateTimeString(),
        ];

        if ($scenario['status'] === 'pending') {
            AssetRepairActivityLog::insert($logs);
            return;
        }

        // Event 2: Approved or Rejected
        $isApproved = ! in_array($scenario['status'], ['rejected']);
        $event2     = $isApproved ? 'approved' : 'rejected';
        $statusSnap = $isApproved ? 'approved' : 'rejected';

        $logs[] = [
            'business_id'             => $businessId,
            'asset_repair_request_id' => $repair->id,
            'performed_by'            => $approver->id,
            'performed_by_name'       => $approver->name,
            'event'                   => $event2,
            'remarks'                 => $repair->approval_remarks,
            'status_snapshot'         => $statusSnap,
            'costing_status_snapshot' => null,
            'performed_at'            => $actionedAt->toDateTimeString(),
        ];

        if ($scenario['costStatus'] === null) {
            AssetRepairActivityLog::insert($logs);
            return;
        }

        // Event 3: Cost approval raised
        $costRaisedAt   = $actionedAt->copy()->addDays(rand(3, 10));
        $costActionedAt = $costRaisedAt->copy()->addDays(rand(1, 3));

        $logs[] = [
            'business_id'             => $businessId,
            'asset_repair_request_id' => $repair->id,
            'performed_by'            => $requester->id,
            'performed_by_name'       => $requester->name,
            'event'                   => 'cost_approval_raised',
            'remarks'                 => 'Actual repair cost identified after vendor inspection. Raising cost approval request.',
            'status_snapshot'         => 'cost_approval_pending',
            'costing_status_snapshot' => 'pending',
            'performed_at'            => $costRaisedAt->toDateTimeString(),
        ];

        if ($scenario['costStatus'] === 'pending') {
            AssetRepairActivityLog::insert($logs);
            return;
        }

        // Event 4: Cost approved or rejected
        $costEvent = $scenario['costStatus'] === 'approved' ? 'cost_approved' : 'cost_rejected';
        $finalStatus = $scenario['costStatus'] === 'approved' ? 'cost_approved' : 'cost_rejected';

        $logs[] = [
            'business_id'             => $businessId,
            'asset_repair_request_id' => $repair->id,
            'performed_by'            => $repair->costing_approved_by ?? $approver->id,
            'performed_by_name'       => Admin::find($repair->costing_approved_by)?->name ?? $approver->name,
            'event'                   => $costEvent,
            'remarks'                 => $repair->costing_remarks,
            'status_snapshot'         => $finalStatus,
            'costing_status_snapshot' => $scenario['costStatus'],
            'performed_at'            => $costActionedAt->toDateTimeString(),
        ];

        AssetRepairActivityLog::insert($logs);
    }

    // ─────────────────────────────────────────────────────────────────────
    // REMARK GENERATORS
    // ─────────────────────────────────────────────────────────────────────

    private function approvalRemark(string $status, string $approverName): string
    {
        $approved = [
            'Approved. Vendor is empanelled. Please ensure asset is collected within 2 working days.',
            'Verified repair necessity. Proceed with vendor — ensure job card is obtained before handing over asset.',
            'Approved. Estimated cost is within ₹50,000 limit. No additional finance approval required.',
            'Approved after physical inspection. Asset condition confirmed as requiring repair. Proceed.',
            'Approved. Please obtain invoice from vendor before raising costing approval.',
        ];
        $rejected = [
            'Rejected. Asset is nearing end-of-life — replacement more cost-effective than repair. Initiate disposal.',
            'Rejected. Vendor not on approved vendor list. Resubmit with empanelled vendor quotation.',
            'Rejected. Insufficient description provided. Please resubmit with detailed fault report and inspection note.',
            'Rejected. Similar repair was done 3 months ago. IT team to investigate root cause before sending for repair again.',
        ];

        return in_array($status, ['approved', 'cost_approval_pending', 'cost_approved', 'cost_rejected'])
            ? $approved[array_rand($approved)]
            : $rejected[array_rand($rejected)];
    }

    private function costingRemark(string $costStatus, string $approverName): string
    {
        $approved = [
            'Costing approved. Finance team notified. Please process vendor payment after receipt of invoice.',
            'Approved. Amount is within budget. Ensure GST invoice is obtained and submitted to accounts within 7 days.',
            'Approved. Payment authorised — NEFT to be processed by accounts by end of week.',
            'Costing approved after verifying vendor invoice. Amount is reasonable for the scope of work.',
        ];
        $rejected = [
            'Rejected. Quoted cost significantly higher than market rate. Obtain 3 competitive quotations and resubmit.',
            'Rejected. Invoice not submitted — cannot approve costing without original GST invoice from vendor.',
            'Rejected. Repair cost exceeds 60% of asset book value. Asset to be disposed instead of repaired.',
        ];

        return $costStatus === 'approved'
            ? $approved[array_rand($approved)]
            : $rejected[array_rand($rejected)];
    }

    private function costingDescription(string $catCode, int $amount): string
    {
        $descriptions = [
            'LAP' => [
                "Labour charges: ₹1,500\nReplacement spare parts (keyboard/display/battery): ₹".($amount - 1500)."\nTotal: ₹{$amount}. GST invoice will be provided by vendor.",
                "Display panel replacement: ₹".($amount - 800)."\nThermal paste + cleaning kit: ₹800\nTotal as per vendor quote: ₹{$amount}.",
            ],
            'DSK' => [
                "SMPS unit replacement: ₹".($amount - 500)."\nLabour: ₹500\nVendor quote ref #VQ-2024. Total: ₹{$amount}.",
            ],
            'MON' => [
                "LCD panel replacement: ₹".($amount - 400)."\nLabour & testing: ₹400. Total: ₹{$amount}.",
            ],
            'PRN' => [
                "Feed roller set: ₹".($amount - 600)."\nLabour: ₹600. Vendor: ".($this->vendors[array_rand($this->vendors)]).". Total: ₹{$amount}.",
            ],
            'SRV' => [
                "Replacement HDD (2TB enterprise SAS): ₹".($amount - 3000)."\nRAID rebuild + testing: ₹3,000\nOnsite service charges: included. Total: ₹{$amount}.",
            ],
            'VEH' => [
                "AC compressor (OEM part): ₹".($amount - 5000)."\nLabour + AC gas refill: ₹5,000\nGST @18% included. Total: ₹{$amount}.",
                "Brake pad set (all 4 wheels): ₹".($amount - 3500)."\nBrake disc skimming: ₹2,000\nLabour: ₹1,500. Total: ₹{$amount}.",
                "Gasket set: ₹".($amount - 4000)."\nEngine oil (5L synthetic): ₹2,000\nLabour: ₹2,000. Total: ₹{$amount}.",
            ],
            'FUR' => [
                "Gas cylinder replacement: ₹".($amount - 300)."\nLabour: ₹300. Total: ₹{$amount}.",
            ],
            'NET' => [
                "Switch board-level repair: ₹".($amount - 500)."\nTesting and delivery: ₹500. Total: ₹{$amount}.",
            ],
        ];

        $list = $descriptions[$catCode] ?? ["Full repair cost including parts and labour. Total: ₹{$amount}."];
        return $list[array_rand($list)];
    }
}
