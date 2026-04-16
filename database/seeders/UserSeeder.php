<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Aarav Sharma', 'email' => 'aarav.sharma@example.com', 'mobile' => '9876543210', 'city_id' => 1, 'address' => '12, Marine Drive', 'status' => 'active'],
            ['name' => 'Priya Patel', 'email' => 'priya.patel@example.com', 'mobile' => '9876543211', 'city_id' => 5, 'address' => '45, CG Road', 'status' => 'active'],
            ['name' => 'Rohit Verma', 'email' => 'rohit.verma@example.com', 'mobile' => '9876543212', 'city_id' => 2, 'address' => '78, Connaught Place', 'status' => 'active'],
            ['name' => 'Ananya Reddy', 'email' => 'ananya.reddy@example.com', 'mobile' => '9876543213', 'city_id' => 4, 'address' => '23, Banjara Hills', 'status' => 'active'],
            ['name' => 'Vikram Singh', 'email' => 'vikram.singh@example.com', 'mobile' => '9876543214', 'city_id' => 9, 'address' => '56, MI Road', 'status' => 'inactive'],
            ['name' => 'Sneha Gupta', 'email' => 'sneha.gupta@example.com', 'mobile' => '9876543215', 'city_id' => 10, 'address' => '89, Hazratganj', 'status' => 'active'],
            ['name' => 'Arjun Nair', 'email' => 'arjun.nair@example.com', 'mobile' => '9876543216', 'city_id' => 3, 'address' => '34, MG Road', 'status' => 'active'],
            ['name' => 'Kavya Iyer', 'email' => 'kavya.iyer@example.com', 'mobile' => '9876543217', 'city_id' => 6, 'address' => '67, T Nagar', 'status' => 'active'],
            ['name' => 'Manish Joshi', 'email' => 'manish.joshi@example.com', 'mobile' => '9876543218', 'city_id' => 8, 'address' => '90, FC Road', 'status' => 'inactive'],
            ['name' => 'Divya Menon', 'email' => 'divya.menon@example.com', 'mobile' => '9876543219', 'city_id' => 7, 'address' => '12, Park Street', 'status' => 'active'],
            ['name' => 'Rajesh Kumar', 'email' => 'rajesh.kumar@example.com', 'mobile' => '9876543220', 'city_id' => 18, 'address' => '45, Boring Road', 'status' => 'active'],
            ['name' => 'Neha Deshmukh', 'email' => 'neha.deshmukh@example.com', 'mobile' => '9876543221', 'city_id' => 13, 'address' => '78, Sitabuldi', 'status' => 'active'],
            ['name' => 'Siddharth Rao', 'email' => 'siddharth.rao@example.com', 'mobile' => '9876543222', 'city_id' => 17, 'address' => '23, Beach Road', 'status' => 'inactive'],
            ['name' => 'Pooja Mishra', 'email' => 'pooja.mishra@example.com', 'mobile' => '9876543223', 'city_id' => 16, 'address' => '56, New Market', 'status' => 'active'],
            ['name' => 'Amit Chauhan', 'email' => 'amit.chauhan@example.com', 'mobile' => '9876543224', 'city_id' => 11, 'address' => '89, Ring Road', 'status' => 'active'],
            ['name' => 'Ritika Saxena', 'email' => 'ritika.saxena@example.com', 'mobile' => '9876543225', 'city_id' => 14, 'address' => '34, Rajwada', 'status' => 'active'],
            ['name' => 'Karan Mehta', 'email' => 'karan.mehta@example.com', 'mobile' => '9876543226', 'city_id' => 19, 'address' => '67, Alkapuri', 'status' => 'inactive'],
            ['name' => 'Shruti Agarwal', 'email' => 'shruti.agarwal@example.com', 'mobile' => '9876543227', 'city_id' => 20, 'address' => '90, Raj Nagar', 'status' => 'active'],
            ['name' => 'Deepak Tiwari', 'email' => 'deepak.tiwari@example.com', 'mobile' => '9876543228', 'city_id' => 12, 'address' => '12, Mall Road', 'status' => 'active'],
            ['name' => 'Megha Bhatt', 'email' => 'megha.bhatt@example.com', 'mobile' => '9876543229', 'city_id' => 15, 'address' => '45, Ghodbunder Road', 'status' => 'active'],
            ['name' => 'Suresh Pillai', 'email' => 'suresh.pillai@example.com', 'mobile' => '9876543230', 'city_id' => 3, 'address' => '78, Whitefield', 'status' => 'inactive'],
            ['name' => 'Anjali Dubey', 'email' => 'anjali.dubey@example.com', 'mobile' => '9876543231', 'city_id' => 10, 'address' => '23, Gomti Nagar', 'status' => 'active'],
            ['name' => 'Nikhil Pandey', 'email' => 'nikhil.pandey@example.com', 'mobile' => '9876543232', 'city_id' => 1, 'address' => '56, Andheri West', 'status' => 'active'],
            ['name' => 'Tanvi Shah', 'email' => 'tanvi.shah@example.com', 'mobile' => '9876543233', 'city_id' => 5, 'address' => '89, Satellite Road', 'status' => 'active'],
            ['name' => 'Gaurav Yadav', 'email' => 'gaurav.yadav@example.com', 'mobile' => '9876543234', 'city_id' => 2, 'address' => '34, Karol Bagh', 'status' => 'inactive'],
            ['name' => 'Ishita Banerjee', 'email' => 'ishita.banerjee@example.com', 'mobile' => '9876543235', 'city_id' => 7, 'address' => '67, Salt Lake', 'status' => 'active'],
            ['name' => 'Varun Kapoor', 'email' => 'varun.kapoor@example.com', 'mobile' => '9876543236', 'city_id' => 8, 'address' => '90, Koregaon Park', 'status' => 'active'],
            ['name' => 'Swati Jain', 'email' => 'swati.jain@example.com', 'mobile' => '9876543237', 'city_id' => 14, 'address' => '12, Sapna Sangeeta', 'status' => 'active'],
            ['name' => 'Harsh Malhotra', 'email' => 'harsh.malhotra@example.com', 'mobile' => '9876543238', 'city_id' => 6, 'address' => '45, Adyar', 'status' => 'inactive'],
            ['name' => 'Nidhi Thakur', 'email' => 'nidhi.thakur@example.com', 'mobile' => '9876543239', 'city_id' => 4, 'address' => '78, Jubilee Hills', 'status' => 'active'],
        ];

        foreach ($users as $user) {
            User::create(array_merge($user, ['password' => Hash::make('password')]));
        }
    }
}
