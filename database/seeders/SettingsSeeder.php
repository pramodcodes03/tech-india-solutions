<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $settings = [
            ['key' => 'company_name', 'value' => 'Apparel & Leather Technics Pvt. Ltd.', 'group' => 'company'],
            ['key' => 'company_address', 'value' => '123, Industrial Area, Phase-1, Ambattur, Chennai - 600058, Tamil Nadu, India', 'group' => 'company'],
            ['key' => 'company_phone', 'value' => '+91 44 2625 1234', 'group' => 'company'],
            ['key' => 'company_email', 'value' => 'info@altechnics.com', 'group' => 'company'],
            ['key' => 'company_gst', 'value' => '33AABCA1234F1Z5', 'group' => 'company'],
            ['key' => 'invoice_prefix', 'value' => 'INV', 'group' => 'document'],
            ['key' => 'quotation_prefix', 'value' => 'QUO', 'group' => 'document'],
            ['key' => 'currency_symbol', 'value' => '₹', 'group' => 'document'],
            ['key' => 'terms_and_conditions', 'value' => '1. Payment is due within 30 days from the date of invoice. 2. Goods once sold will not be taken back. 3. Interest at 18% p.a. will be charged on overdue payments. 4. All disputes subject to Chennai jurisdiction.', 'group' => 'document'],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->updateOrInsert(
                ['key' => $setting['key']],
                $setting + ['created_at' => $now, 'updated_at' => $now],
            );
        }
    }
}
