<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        $cities = [
            ['name' => 'Mumbai', 'state' => 'Maharashtra'],
            ['name' => 'Delhi', 'state' => 'Delhi'],
            ['name' => 'Bangalore', 'state' => 'Karnataka'],
            ['name' => 'Hyderabad', 'state' => 'Telangana'],
            ['name' => 'Ahmedabad', 'state' => 'Gujarat'],
            ['name' => 'Chennai', 'state' => 'Tamil Nadu'],
            ['name' => 'Kolkata', 'state' => 'West Bengal'],
            ['name' => 'Pune', 'state' => 'Maharashtra'],
            ['name' => 'Jaipur', 'state' => 'Rajasthan'],
            ['name' => 'Lucknow', 'state' => 'Uttar Pradesh'],
            ['name' => 'Surat', 'state' => 'Gujarat'],
            ['name' => 'Kanpur', 'state' => 'Uttar Pradesh'],
            ['name' => 'Nagpur', 'state' => 'Maharashtra'],
            ['name' => 'Indore', 'state' => 'Madhya Pradesh'],
            ['name' => 'Thane', 'state' => 'Maharashtra'],
            ['name' => 'Bhopal', 'state' => 'Madhya Pradesh'],
            ['name' => 'Visakhapatnam', 'state' => 'Andhra Pradesh'],
            ['name' => 'Patna', 'state' => 'Bihar'],
            ['name' => 'Vadodara', 'state' => 'Gujarat'],
            ['name' => 'Ghaziabad', 'state' => 'Uttar Pradesh'],
        ];

        foreach ($cities as $city) {
            City::create($city);
        }
    }
}
