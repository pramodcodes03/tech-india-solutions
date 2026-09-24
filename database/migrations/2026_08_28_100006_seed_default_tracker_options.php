<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seed each existing business with a usable starter set of dropdown values so
 * the trackers are not an empty form on day one. Everything here is editable
 * (or deletable) from HR → Trackers → Tracker Settings.
 */
return new class extends Migration
{
    private array $defaults = [
        'break_type' => ['Tea Break', 'Lunch Break', 'Personal', 'Official Work', 'Medical', 'Other'],
        'visitor_source' => ['Walk-in', 'Referral', 'Naukri', 'LinkedIn', 'Indeed', 'Consultancy', 'Campus', 'Website', 'Other'],
        'visit_purpose' => ['Interview', 'Client Meeting', 'Vendor Visit', 'Delivery', 'Document Submission', 'Personal Visit', 'Other'],
    ];

    public function up(): void
    {
        $now = now();

        foreach (DB::table('businesses')->pluck('id') as $businessId) {
            $rows = [];
            foreach ($this->defaults as $type => $names) {
                foreach ($names as $i => $name) {
                    $rows[] = [
                        'business_id' => $businessId,
                        'type' => $type,
                        'name' => $name,
                        'sort_order' => $i + 1,
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            // insertOrIgnore keeps a re-run (or a partially seeded business) safe
            // against the (business_id, type, name) unique index.
            DB::table('tracker_options')->insertOrIgnore($rows);
        }
    }

    public function down(): void
    {
        foreach ($this->defaults as $type => $names) {
            DB::table('tracker_options')->where('type', $type)->whereIn('name', $names)->delete();
        }
    }
};
