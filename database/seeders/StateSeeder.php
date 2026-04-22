<?php

namespace Database\Seeders;

use App\Models\State;
use Illuminate\Database\Seeder;

class StateSeeder extends Seeder
{
    public function run(): void
    {
        $states = [
            ['Andhra Pradesh', 'AP'], ['Arunachal Pradesh', 'AR'], ['Assam', 'AS'],
            ['Bihar', 'BR'], ['Chhattisgarh', 'CG'], ['Goa', 'GA'], ['Gujarat', 'GJ'],
            ['Haryana', 'HR'], ['Himachal Pradesh', 'HP'], ['Jharkhand', 'JH'],
            ['Karnataka', 'KA'], ['Kerala', 'KL'], ['Madhya Pradesh', 'MP'],
            ['Maharashtra', 'MH'], ['Manipur', 'MN'], ['Meghalaya', 'ML'], ['Mizoram', 'MZ'],
            ['Nagaland', 'NL'], ['Odisha', 'OD'], ['Punjab', 'PB'], ['Rajasthan', 'RJ'],
            ['Sikkim', 'SK'], ['Tamil Nadu', 'TN'], ['Telangana', 'TG'], ['Tripura', 'TR'],
            ['Uttar Pradesh', 'UP'], ['Uttarakhand', 'UK'], ['West Bengal', 'WB'],
            ['Andaman and Nicobar Islands', 'AN'], ['Chandigarh', 'CH'],
            ['Dadra and Nagar Haveli and Daman and Diu', 'DN'], ['Delhi', 'DL'],
            ['Jammu and Kashmir', 'JK'], ['Ladakh', 'LA'], ['Lakshadweep', 'LD'],
            ['Puducherry', 'PY'],
        ];

        foreach ($states as [$name, $code]) {
            State::updateOrCreate(['name' => $name], ['code' => $code, 'is_active' => true]);
        }
    }
}
