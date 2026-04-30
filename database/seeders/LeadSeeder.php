<?php

namespace Database\Seeders;

use App\Models\Admin;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeadSeeder extends Seeder
{
    public function run(): void
    {
        $businessId = app(\App\Support\Tenancy\CurrentBusiness::class)->id();
        $now = Carbon::now();

        // Admin IDs: 1=Super Admin, 2=Rajesh(Admin), 3=Priya(Sales), 4=Suresh(Inventory), 5=Lakshmi(Accounts), 6=Mohammed(Service), 7=Anita(Viewer)
        $salesAdminId = 3; // Priya - Sales
        $adminId = 2; // Rajesh - Admin

        $leads = [
            // 2 new
            ['code' => 'LEAD-0001', 'name' => 'Ramesh Babu',         'company' => 'Babu Leather Mart',             'phone' => '+91 98400 31001', 'email' => 'ramesh@babuleather.in',     'source' => 'website',    'status' => 'new',       'assigned_to' => $salesAdminId, 'expected_value' => 150000, 'next_follow_up_at' => $now->copy()->addDays(2)->format('Y-m-d H:i:s'),  'created_at' => $now->copy()->subDays(3)],
            ['code' => 'LEAD-0002', 'name' => 'Kavitha Sundaram',    'company' => 'Sundaram Retail',               'phone' => '+91 98400 31002', 'email' => 'kavitha@sundaramretail.in', 'source' => 'referral',   'status' => 'new',       'assigned_to' => $salesAdminId, 'expected_value' => 200000, 'next_follow_up_at' => $now->copy()->addDays(1)->format('Y-m-d H:i:s'),  'created_at' => $now->copy()->subDays(1)],
            // 2 contacted
            ['code' => 'LEAD-0003', 'name' => 'Vinod Kapoor',        'company' => 'Kapoor Shoes',                  'phone' => '+91 99100 31003', 'email' => 'vinod@kapoorshoes.com',     'source' => 'trade_fair', 'status' => 'contacted', 'assigned_to' => $salesAdminId, 'expected_value' => 350000, 'next_follow_up_at' => $now->copy()->addDays(5)->format('Y-m-d H:i:s'),  'created_at' => $now->copy()->subDays(10)],
            ['code' => 'LEAD-0004', 'name' => 'Sonia Malhotra',      'company' => 'Malhotra Fashion Hub',          'phone' => '+91 98200 31004', 'email' => 'sonia@malhotrafashion.in',  'source' => 'website',    'status' => 'contacted', 'assigned_to' => $adminId,      'expected_value' => 180000, 'next_follow_up_at' => $now->copy()->addDays(3)->format('Y-m-d H:i:s'),  'created_at' => $now->copy()->subDays(8)],
            // 2 qualified
            ['code' => 'LEAD-0005', 'name' => 'Dinesh Choudhary',    'company' => 'Choudhary Exports',             'phone' => '+91 94140 31005', 'email' => 'dinesh@choudharyexp.com',   'source' => 'referral',   'status' => 'qualified', 'assigned_to' => $salesAdminId, 'expected_value' => 500000, 'next_follow_up_at' => $now->copy()->addDays(7)->format('Y-m-d H:i:s'),  'created_at' => $now->copy()->subDays(20)],
            ['code' => 'LEAD-0006', 'name' => 'Prakash Menon',       'company' => 'Menon Bags & Accessories',      'phone' => '+91 94470 31006', 'email' => 'prakash@menonbags.in',      'source' => 'cold_call',  'status' => 'qualified', 'assigned_to' => $salesAdminId, 'expected_value' => 280000, 'next_follow_up_at' => $now->copy()->addDays(4)->format('Y-m-d H:i:s'),  'created_at' => $now->copy()->subDays(15)],
            // 2 proposal
            ['code' => 'LEAD-0007', 'name' => 'Geeta Sharma',        'company' => 'Sharma Retail Chain',           'phone' => '+91 98720 31007', 'email' => 'geeta@sharmaretail.in',     'source' => 'trade_fair', 'status' => 'proposal',  'assigned_to' => $salesAdminId, 'expected_value' => 420000, 'next_follow_up_at' => $now->copy()->addDays(10)->format('Y-m-d H:i:s'), 'created_at' => $now->copy()->subDays(30)],
            ['code' => 'LEAD-0008', 'name' => 'Manoj Jain',          'company' => 'Jain Leather International',    'phone' => '+91 98250 31008', 'email' => 'manoj@jainleather.com',     'source' => 'website',    'status' => 'proposal',  'assigned_to' => $adminId,      'expected_value' => 600000, 'next_follow_up_at' => $now->copy()->addDays(6)->format('Y-m-d H:i:s'),  'created_at' => $now->copy()->subDays(25)],
            // 1 won
            ['code' => 'LEAD-0009', 'name' => 'Ashok Reddy',         'company' => 'Reddy Leather Works',           'phone' => '+91 98490 31009', 'email' => 'ashok@reddyleather.in',     'source' => 'referral',   'status' => 'won',       'assigned_to' => $salesAdminId, 'expected_value' => 375000, 'next_follow_up_at' => null,                                              'created_at' => $now->copy()->subDays(45)],
            // 1 lost
            ['code' => 'LEAD-0010', 'name' => 'Pankaj Mishra',       'company' => 'Mishra Garments',               'phone' => '+91 94150 31010', 'email' => 'pankaj@mishragarments.in',  'source' => 'cold_call',  'status' => 'lost',      'assigned_to' => $salesAdminId, 'expected_value' => 120000, 'next_follow_up_at' => null,                                              'created_at' => $now->copy()->subDays(40)],
        ];

        $superAdminId = 1;

        foreach ($leads as $lead) {
            $createdAt = $lead['created_at'];
            unset($lead['created_at']);
            $lead['business_id'] = $businessId;
            $lead['notes'] = null;
            $lead['created_by'] = $superAdminId;
            $lead['updated_by'] = null;
            $lead['deleted_by'] = null;
            $lead['created_at'] = $createdAt;
            $lead['updated_at'] = $createdAt;
            $lead['deleted_at'] = null;

            $leadId = DB::table('leads')->insertGetId($lead);

            // Create 2-3 LeadActivity records per lead
            $activities = $this->getActivities($lead['status'], $leadId, $createdAt, $salesAdminId, $businessId);
            DB::table('lead_activities')->insert($activities);
        }
    }

    private function getActivities(string $status, int $leadId, $createdAt, int $salesAdminId, int $businessId): array
    {
        $base = Carbon::parse($createdAt);
        $activities = [];

        // All leads get a "created" activity
        $activities[] = [
            'business_id' => $businessId,
            'lead_id' => $leadId,
            'type' => 'note',
            'description' => 'Lead created and assigned to sales team.',
            'created_by' => 1,
            'created_at' => $base,
            'updated_at' => $base,
        ];

        if (in_array($status, ['contacted', 'qualified', 'proposal', 'won', 'lost'])) {
            $activities[] = [
                'business_id' => $businessId,
                'lead_id' => $leadId,
                'type' => 'call',
                'description' => 'Initial call made. Customer showed interest in leather goods catalog.',
                'created_by' => $salesAdminId,
                'created_at' => $base->copy()->addDays(1),
                'updated_at' => $base->copy()->addDays(1),
            ];
        }

        if (in_array($status, ['qualified', 'proposal', 'won', 'lost'])) {
            $activities[] = [
                'business_id' => $businessId,
                'lead_id' => $leadId,
                'type' => 'meeting',
                'description' => 'Meeting conducted. Discussed product requirements and pricing.',
                'created_by' => $salesAdminId,
                'created_at' => $base->copy()->addDays(5),
                'updated_at' => $base->copy()->addDays(5),
            ];
        }

        if (in_array($status, ['proposal', 'won'])) {
            $activities[] = [
                'business_id' => $businessId,
                'lead_id' => $leadId,
                'type' => 'email',
                'description' => 'Quotation sent via email for review.',
                'created_by' => $salesAdminId,
                'created_at' => $base->copy()->addDays(8),
                'updated_at' => $base->copy()->addDays(8),
            ];
        }

        if ($status === 'won') {
            $activities[] = [
                'business_id' => $businessId,
                'lead_id' => $leadId,
                'type' => 'note',
                'description' => 'Deal closed. Customer converted. First order placed.',
                'created_by' => $salesAdminId,
                'created_at' => $base->copy()->addDays(12),
                'updated_at' => $base->copy()->addDays(12),
            ];
        }

        if ($status === 'lost') {
            $activities[] = [
                'business_id' => $businessId,
                'lead_id' => $leadId,
                'type' => 'note',
                'description' => 'Lead lost. Customer went with a competitor offering lower prices.',
                'created_by' => $salesAdminId,
                'created_at' => $base->copy()->addDays(10),
                'updated_at' => $base->copy()->addDays(10),
            ];
        }

        return $activities;
    }
}
