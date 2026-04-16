<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VendorSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = Admin::first()->id;
        $now = now();

        $vendors = [
            ['code' => 'VEN-0001', 'name' => 'Ramesh Tannery',           'company' => 'Ramesh Tannery & Hides',       'gst_number' => '33AABCR1001A1Z1', 'email' => 'sales@rameshtannery.in',       'phone' => '+91 98400 21001', 'address' => '12, Tannery Road, Vaniyambadi',        'city' => 'Vaniyambadi',   'state' => 'Tamil Nadu',     'pincode' => '635751'],
            ['code' => 'VEN-0002', 'name' => 'Kanpur Leather Corp.',     'company' => 'Kanpur Leather Corporation',    'gst_number' => '09AABCK2002B1Z2', 'email' => 'info@kanpurleather.com',       'phone' => '+91 94150 21002', 'address' => '45, Jajmau Industrial Area',           'city' => 'Kanpur',        'state' => 'Uttar Pradesh',  'pincode' => '208010'],
            ['code' => 'VEN-0003', 'name' => 'South India Hides',        'company' => 'South India Hides & Skins',    'gst_number' => '33AABCS3003C1Z3', 'email' => 'contact@sihides.in',           'phone' => '+91 98400 21003', 'address' => '78, Pallavaram Industrial Estate',     'city' => 'Chennai',       'state' => 'Tamil Nadu',     'pincode' => '600043'],
            ['code' => 'VEN-0004', 'name' => 'Coimbatore Textiles',      'company' => 'Coimbatore Textile Mills',      'gst_number' => '33AABCC4004D1Z4', 'email' => 'orders@cbtextiles.in',         'phone' => '+91 98430 21004', 'address' => '23, SIDCO Industrial Estate, Kurichi', 'city' => 'Coimbatore',    'state' => 'Tamil Nadu',     'pincode' => '641021'],
            ['code' => 'VEN-0005', 'name' => 'Tirupur Knits',            'company' => 'Tirupur Knits & Fabrics',       'gst_number' => '33AABCT5005E1Z5', 'email' => 'sales@tirupurknits.in',        'phone' => '+91 98430 21005', 'address' => '56, SIPCOT Industrial Complex',        'city' => 'Tirupur',       'state' => 'Tamil Nadu',     'pincode' => '641603'],
            ['code' => 'VEN-0006', 'name' => 'Gujarat Chemicals',        'company' => 'Gujarat Chemical Industries',   'gst_number' => '24AABCG6006F1Z6', 'email' => 'info@gujchem.com',             'phone' => '+91 98250 21006', 'address' => '89, GIDC, Vatva',                      'city' => 'Ahmedabad',     'state' => 'Gujarat',        'pincode' => '382445'],
            ['code' => 'VEN-0007', 'name' => 'Surat Dyes & Chemicals',   'company' => 'Surat Dyes & Chemicals Co.',   'gst_number' => '24AABCS7007G1Z7', 'email' => 'sales@suratdyes.in',           'phone' => '+91 98250 21007', 'address' => '34, Pandesara GIDC',                   'city' => 'Surat',         'state' => 'Gujarat',        'pincode' => '394221'],
            ['code' => 'VEN-0008', 'name' => 'Mumbai Hardware Supply',   'company' => 'Mumbai Hardware & Metal Works', 'gst_number' => '27AABCM8008H1Z8', 'email' => 'orders@mumbaihw.com',          'phone' => '+91 98200 21008', 'address' => '67, Crawford Market',                  'city' => 'Mumbai',        'state' => 'Maharashtra',    'pincode' => '400001'],
            ['code' => 'VEN-0009', 'name' => 'YKK India',                'company' => 'YKK India Pvt. Ltd.',           'gst_number' => '06AABCY9009I1Z9', 'email' => 'b2b@ykkindia.com',             'phone' => '+91 12434 21009', 'address' => 'Plot 5, IMT Bawal',                    'city' => 'Rewari',        'state' => 'Haryana',        'pincode' => '123501'],
            ['code' => 'VEN-0010', 'name' => 'Chennai Packaging',        'company' => 'Chennai Packaging Solutions',   'gst_number' => '33AABCC0010J1ZA', 'email' => 'info@chennaipack.in',          'phone' => '+91 98400 21010', 'address' => '90, Guindy Industrial Estate',         'city' => 'Chennai',       'state' => 'Tamil Nadu',     'pincode' => '600032'],
            ['code' => 'VEN-0011', 'name' => 'Kolkata Thread Works',     'company' => 'Kolkata Thread & Yarn Works',   'gst_number' => '19AABCK0011K1ZB', 'email' => 'sales@kolkatathread.in',       'phone' => '+91 98310 21011', 'address' => '23, Burrabazar',                       'city' => 'Kolkata',       'state' => 'West Bengal',    'pincode' => '700007'],
            ['code' => 'VEN-0012', 'name' => 'Agra Leather Suppliers',   'company' => 'Agra Leather Supply Co.',       'gst_number' => '09AABCA0012L1ZC', 'email' => 'info@agraleather.com',         'phone' => '+91 95280 21012', 'address' => '45, Hing Ki Mandi',                    'city' => 'Agra',          'state' => 'Uttar Pradesh',  'pincode' => '282003'],
            ['code' => 'VEN-0013', 'name' => 'Bangalore Tools Co.',      'company' => 'Bangalore Tools & Equipment',   'gst_number' => '29AABCB0013M1ZD', 'email' => 'sales@blrtools.in',            'phone' => '+91 98450 21013', 'address' => '78, Peenya 2nd Stage',                 'city' => 'Bangalore',     'state' => 'Karnataka',      'pincode' => '560058'],
            ['code' => 'VEN-0014', 'name' => 'Ambur Tanning Co.',        'company' => 'Ambur Tanning Company',         'gst_number' => '33AABCA0014N1ZE', 'email' => 'sales@amburtanning.in',        'phone' => '+91 98400 21014', 'address' => '12, Industrial Area, Ambur',           'city' => 'Ambur',         'state' => 'Tamil Nadu',     'pincode' => '635802'],
            ['code' => 'VEN-0015', 'name' => 'Sivakasi Print & Pack',    'company' => 'Sivakasi Print & Packaging',    'gst_number' => '33AABCS0015O1ZF', 'email' => 'orders@sivakasipack.in',       'phone' => '+91 94870 21015', 'address' => '56, Sivakasi Main Road',               'city' => 'Sivakasi',      'state' => 'Tamil Nadu',     'pincode' => '626123'],
        ];

        $rows = [];
        foreach ($vendors as $v) {
            $v['country'] = 'India';
            $v['notes'] = null;
            $v['status'] = 'active';
            $v['created_by'] = $adminId;
            $v['updated_by'] = null;
            $v['deleted_by'] = null;
            $v['created_at'] = $now;
            $v['updated_at'] = $now;
            $v['deleted_at'] = null;
            $rows[] = $v;
        }

        DB::table('vendors')->insert($rows);
    }
}
