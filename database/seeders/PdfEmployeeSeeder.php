<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PdfEmployeeSeeder extends Seeder
{
    /**
     * Seed the 10 employees from the Tech India Solutions attendance PDF
     * split across two businesses (5 each) — every employee_code is unique
     * globally, matching the new validation rule.
     *
     * Idempotent — re-running updates rows in place.
     *
     * Run with:
     *   php artisan db:seed --class=PdfEmployeeSeeder
     */
    public function run(): void
    {
        $rows = [
            // Business 1 — Tech India Solutions
            ['biz' => 1, 'code' => '2244', 'first' => 'Bharti',    'last' => 'Singh',   'gender' => 'female'],
            ['biz' => 1, 'code' => '2245', 'first' => 'Tripurari', 'last' => null,      'gender' => 'male'],
            ['biz' => 1, 'code' => '2249', 'first' => 'Arpit',     'last' => null,      'gender' => 'male'],
            ['biz' => 1, 'code' => '2263', 'first' => 'Mohit',     'last' => null,      'gender' => 'male'],
            ['biz' => 1, 'code' => '2269', 'first' => 'Raman',     'last' => null,      'gender' => 'male'],

            // Business 2 — AL Engineering Works
            ['biz' => 2, 'code' => '2272', 'first' => 'Santosh',   'last' => null,      'gender' => 'male'],
            ['biz' => 2, 'code' => '2273', 'first' => 'Dolly',     'last' => null,      'gender' => 'male'],
            ['biz' => 2, 'code' => '2274', 'first' => 'Harshit',   'last' => null,      'gender' => 'male'],
            ['biz' => 2, 'code' => '2275', 'first' => 'Pooja',     'last' => null,      'gender' => 'female'],
            ['biz' => 2, 'code' => '2276', 'first' => 'Mahima',    'last' => 'Kashyap', 'gender' => 'female'],
        ];

        $domains = [
            1 => 'techindia.test',
            2 => 'al-engineering.test',
        ];

        $password = Hash::make('password');
        $now = now();

        foreach ($rows as $r) {
            $domain = $domains[$r['biz']];

            DB::table('employees')->updateOrInsert(
                ['employee_code' => $r['code']],
                [
                    'business_id'     => $r['biz'],
                    'card_no'         => $r['code'],
                    'email'           => strtolower($r['first']).'.'.$r['code'].'@'.$domain,
                    'password'        => $password,
                    'first_name'      => $r['first'],
                    'last_name'       => $r['last'],
                    'gender'          => $r['gender'],
                    'employment_type' => 'full_time',
                    'work_mode'       => 'on_site',
                    'bgv_status'      => 'pending',
                    'status'          => 'active',
                    'country'         => 'India',
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]
            );
        }

        $this->command->info('Seeded '.count($rows).' employees (5 per business across 2 businesses).');
    }
}
