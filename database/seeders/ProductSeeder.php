<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $businessId = app(\App\Support\Tenancy\CurrentBusiness::class)->id();
        $adminId = Admin::where('business_id', $businessId)->first()?->id ?? Admin::first()->id;
        $now = now();

        // Category IDs will be 1-8 based on insertion order in CategorySeeder
        $catLeather = 1;
        $catApparel = 2;
        $catAccessory = 3;
        $catFootwear = 4;
        $catBags = 5;
        $catRaw = 6;
        $catTools = 7;
        $catPackaging = 8;

        $products = [
            // Leather Goods (cat 1)
            ['code' => 'PRD-0001', 'name' => 'Genuine Leather Belt - Brown',       'category_id' => $catLeather,  'hsn_code' => '4205', 'unit' => 'pcs', 'purchase_price' => 280, 'selling_price' => 499, 'mrp' => 599, 'tax_percent' => 12, 'reorder_level' => 25, 'description' => 'Premium full-grain leather belt with brass buckle'],
            ['code' => 'PRD-0002', 'name' => 'Genuine Leather Belt - Black',       'category_id' => $catLeather,  'hsn_code' => '4205', 'unit' => 'pcs', 'purchase_price' => 280, 'selling_price' => 499, 'mrp' => 599, 'tax_percent' => 12, 'reorder_level' => 25, 'description' => 'Premium full-grain leather belt in black'],
            ['code' => 'PRD-0003', 'name' => 'Leather Wallet - Bifold',            'category_id' => $catLeather,  'hsn_code' => '4205', 'unit' => 'pcs', 'purchase_price' => 350, 'selling_price' => 699, 'mrp' => 899, 'tax_percent' => 12, 'reorder_level' => 30, 'description' => 'Genuine leather bifold wallet with RFID blocking'],
            ['code' => 'PRD-0004', 'name' => 'Leather Card Holder',                'category_id' => $catLeather,  'hsn_code' => '4205', 'unit' => 'pcs', 'purchase_price' => 150, 'selling_price' => 349, 'mrp' => 449, 'tax_percent' => 12, 'reorder_level' => 40, 'description' => 'Slim leather card holder with 6 slots'],
            ['code' => 'PRD-0005', 'name' => 'Leather Passport Cover',             'category_id' => $catLeather,  'hsn_code' => '4205', 'unit' => 'pcs', 'purchase_price' => 200, 'selling_price' => 449, 'mrp' => 549, 'tax_percent' => 12, 'reorder_level' => 20, 'description' => 'Premium leather passport holder'],
            // Apparel (cat 2)
            ['code' => 'PRD-0006', 'name' => 'Cotton Polo T-Shirt - White',        'category_id' => $catApparel,  'hsn_code' => '6109', 'unit' => 'pcs', 'purchase_price' => 250, 'selling_price' => 599, 'mrp' => 799, 'tax_percent' => 5,  'reorder_level' => 50, 'description' => '100% cotton pique polo t-shirt'],
            ['code' => 'PRD-0007', 'name' => 'Cotton Polo T-Shirt - Navy',         'category_id' => $catApparel,  'hsn_code' => '6109', 'unit' => 'pcs', 'purchase_price' => 250, 'selling_price' => 599, 'mrp' => 799, 'tax_percent' => 5,  'reorder_level' => 50, 'description' => '100% cotton pique polo t-shirt in navy blue'],
            ['code' => 'PRD-0008', 'name' => 'Suede Jacket - Tan',                 'category_id' => $catApparel,  'hsn_code' => '4203', 'unit' => 'pcs', 'purchase_price' => 2800, 'selling_price' => 5499, 'mrp' => 6999, 'tax_percent' => 12, 'reorder_level' => 10, 'description' => 'Premium suede leather jacket'],
            ['code' => 'PRD-0009', 'name' => 'Leather Bomber Jacket - Black',      'category_id' => $catApparel,  'hsn_code' => '4203', 'unit' => 'pcs', 'purchase_price' => 3200, 'selling_price' => 6499, 'mrp' => 7999, 'tax_percent' => 12, 'reorder_level' => 8,  'description' => 'Classic leather bomber jacket'],
            ['code' => 'PRD-0010', 'name' => 'Denim Shirt - Blue',                 'category_id' => $catApparel,  'hsn_code' => '6205', 'unit' => 'pcs', 'purchase_price' => 380, 'selling_price' => 899, 'mrp' => 1099, 'tax_percent' => 5,  'reorder_level' => 30, 'description' => 'Slim-fit denim shirt'],
            // Accessories (cat 3)
            ['code' => 'PRD-0011', 'name' => 'Leather Keychain',                   'category_id' => $catAccessory, 'hsn_code' => '4205', 'unit' => 'pcs', 'purchase_price' => 60,  'selling_price' => 149, 'mrp' => 199, 'tax_percent' => 12, 'reorder_level' => 50, 'description' => 'Handcrafted leather keychain'],
            ['code' => 'PRD-0012', 'name' => 'Leather Gloves - Pair',              'category_id' => $catAccessory, 'hsn_code' => '4203', 'unit' => 'pcs', 'purchase_price' => 450, 'selling_price' => 899, 'mrp' => 1099, 'tax_percent' => 12, 'reorder_level' => 15, 'description' => 'Genuine sheepskin leather gloves'],
            ['code' => 'PRD-0013', 'name' => 'Silk Scarf - Printed',               'category_id' => $catAccessory, 'hsn_code' => '6214', 'unit' => 'pcs', 'purchase_price' => 300, 'selling_price' => 699, 'mrp' => 899, 'tax_percent' => 5,  'reorder_level' => 20, 'description' => 'Pure silk printed scarf'],
            ['code' => 'PRD-0014', 'name' => 'Leather Bracelet',                   'category_id' => $catAccessory, 'hsn_code' => '4205', 'unit' => 'pcs', 'purchase_price' => 80,  'selling_price' => 199, 'mrp' => 249, 'tax_percent' => 12, 'reorder_level' => 40, 'description' => 'Braided leather bracelet'],
            ['code' => 'PRD-0015', 'name' => 'Leather Watch Strap',                'category_id' => $catAccessory, 'hsn_code' => '9113', 'unit' => 'pcs', 'purchase_price' => 120, 'selling_price' => 299, 'mrp' => 399, 'tax_percent' => 18, 'reorder_level' => 25, 'description' => 'Genuine calf leather watch strap 22mm'],
            // Footwear (cat 4)
            ['code' => 'PRD-0016', 'name' => 'Leather Oxford Shoes - Brown',       'category_id' => $catFootwear,  'hsn_code' => '6403', 'unit' => 'pcs', 'purchase_price' => 1200, 'selling_price' => 2499, 'mrp' => 2999, 'tax_percent' => 18, 'reorder_level' => 15, 'description' => 'Formal full-grain leather oxford shoes'],
            ['code' => 'PRD-0017', 'name' => 'Leather Loafers - Black',            'category_id' => $catFootwear,  'hsn_code' => '6403', 'unit' => 'pcs', 'purchase_price' => 900,  'selling_price' => 1899, 'mrp' => 2299, 'tax_percent' => 18, 'reorder_level' => 15, 'description' => 'Penny loafers in polished leather'],
            ['code' => 'PRD-0018', 'name' => 'Leather Sandals',                    'category_id' => $catFootwear,  'hsn_code' => '6403', 'unit' => 'pcs', 'purchase_price' => 400,  'selling_price' => 899, 'mrp' => 1099, 'tax_percent' => 18, 'reorder_level' => 20, 'description' => 'Handcrafted leather sandals'],
            ['code' => 'PRD-0019', 'name' => 'Chelsea Boots - Tan',                'category_id' => $catFootwear,  'hsn_code' => '6403', 'unit' => 'pcs', 'purchase_price' => 1800, 'selling_price' => 3499, 'mrp' => 4299, 'tax_percent' => 18, 'reorder_level' => 10, 'description' => 'Classic suede Chelsea boots'],
            ['code' => 'PRD-0020', 'name' => 'Leather Chappals',                   'category_id' => $catFootwear,  'hsn_code' => '6403', 'unit' => 'pcs', 'purchase_price' => 250,  'selling_price' => 549, 'mrp' => 699, 'tax_percent' => 18, 'reorder_level' => 30, 'description' => 'Traditional leather chappals'],
            // Bags & Luggage (cat 5)
            ['code' => 'PRD-0021', 'name' => 'Canvas Tote Bag',                    'category_id' => $catBags,      'hsn_code' => '4202', 'unit' => 'pcs', 'purchase_price' => 350,  'selling_price' => 799, 'mrp' => 999, 'tax_percent' => 18, 'reorder_level' => 20, 'description' => 'Heavy-duty canvas tote with leather handles'],
            ['code' => 'PRD-0022', 'name' => 'Leather Laptop Bag',                 'category_id' => $catBags,      'hsn_code' => '4202', 'unit' => 'pcs', 'purchase_price' => 1500, 'selling_price' => 2999, 'mrp' => 3699, 'tax_percent' => 18, 'reorder_level' => 10, 'description' => 'Premium leather laptop bag fits 15-inch'],
            ['code' => 'PRD-0023', 'name' => 'Leather Duffel Bag',                 'category_id' => $catBags,      'hsn_code' => '4202', 'unit' => 'pcs', 'purchase_price' => 2200, 'selling_price' => 4499, 'mrp' => 5499, 'tax_percent' => 18, 'reorder_level' => 8,  'description' => 'Weekender duffel in full-grain leather'],
            ['code' => 'PRD-0024', 'name' => 'Ladies Handbag - Red',               'category_id' => $catBags,      'hsn_code' => '4202', 'unit' => 'pcs', 'purchase_price' => 800,  'selling_price' => 1699, 'mrp' => 2099, 'tax_percent' => 18, 'reorder_level' => 15, 'description' => 'Genuine leather ladies handbag'],
            ['code' => 'PRD-0025', 'name' => 'Leather Sling Bag',                  'category_id' => $catBags,      'hsn_code' => '4202', 'unit' => 'pcs', 'purchase_price' => 500,  'selling_price' => 999, 'mrp' => 1299, 'tax_percent' => 18, 'reorder_level' => 20, 'description' => 'Compact leather crossbody sling bag'],
            // Raw Materials (cat 6)
            ['code' => 'PRD-0026', 'name' => 'Leather Hide (Full Grain) - Sq.ft',  'category_id' => $catRaw,       'hsn_code' => '4107', 'unit' => 'kg',  'purchase_price' => 180, 'selling_price' => 280, 'mrp' => 280, 'tax_percent' => 5,  'reorder_level' => 50, 'description' => 'Full-grain vegetable tanned cowhide per sq.ft'],
            ['code' => 'PRD-0027', 'name' => 'Split Leather Hide - Sq.ft',         'category_id' => $catRaw,       'hsn_code' => '4107', 'unit' => 'kg',  'purchase_price' => 90,  'selling_price' => 150, 'mrp' => 150, 'tax_percent' => 5,  'reorder_level' => 50, 'description' => 'Split leather per sq.ft for lining'],
            ['code' => 'PRD-0028', 'name' => 'Cotton Fabric - White (per mtr)',    'category_id' => $catRaw,       'hsn_code' => '5208', 'unit' => 'mtr', 'purchase_price' => 120, 'selling_price' => 200, 'mrp' => 200, 'tax_percent' => 5,  'reorder_level' => 100, 'description' => '60-inch width premium cotton fabric'],
            ['code' => 'PRD-0029', 'name' => 'Denim Fabric - Blue (per mtr)',      'category_id' => $catRaw,       'hsn_code' => '5211', 'unit' => 'mtr', 'purchase_price' => 180, 'selling_price' => 280, 'mrp' => 280, 'tax_percent' => 5,  'reorder_level' => 80, 'description' => 'Premium selvedge denim fabric'],
            ['code' => 'PRD-0030', 'name' => 'Stitching Thread Spool - Black',     'category_id' => $catRaw,       'hsn_code' => '5401', 'unit' => 'pcs', 'purchase_price' => 45,  'selling_price' => 80,  'mrp' => 80,  'tax_percent' => 12, 'reorder_level' => 50, 'description' => 'Heavy-duty nylon stitching thread 200m spool'],
            ['code' => 'PRD-0031', 'name' => 'Metal Buckle - Brass (25mm)',        'category_id' => $catRaw,       'hsn_code' => '8308', 'unit' => 'pcs', 'purchase_price' => 25,  'selling_price' => 50,  'mrp' => 50,  'tax_percent' => 18, 'reorder_level' => 100, 'description' => 'Solid brass belt buckle 25mm'],
            ['code' => 'PRD-0032', 'name' => 'Metal Zipper - YKK (20cm)',          'category_id' => $catRaw,       'hsn_code' => '9607', 'unit' => 'pcs', 'purchase_price' => 15,  'selling_price' => 30,  'mrp' => 30,  'tax_percent' => 18, 'reorder_level' => 200, 'description' => 'YKK metal zipper 20cm for bags'],
            ['code' => 'PRD-0033', 'name' => 'Leather Dye - Brown (500ml)',        'category_id' => $catRaw,       'hsn_code' => '3204', 'unit' => 'pcs', 'purchase_price' => 220, 'selling_price' => 380, 'mrp' => 380, 'tax_percent' => 18, 'reorder_level' => 20, 'description' => 'Alcohol-based leather dye 500ml bottle'],
            // Tools & Equipment (cat 7)
            ['code' => 'PRD-0034', 'name' => 'Leather Cutting Knife',              'category_id' => $catTools,     'hsn_code' => '8211', 'unit' => 'pcs', 'purchase_price' => 350,  'selling_price' => 599,  'mrp' => 699,  'tax_percent' => 18, 'reorder_level' => 10, 'description' => 'Professional rotary leather cutting knife'],
            ['code' => 'PRD-0035', 'name' => 'Hand Stitching Awl Set',             'category_id' => $catTools,     'hsn_code' => '8205', 'unit' => 'pcs', 'purchase_price' => 180,  'selling_price' => 349,  'mrp' => 449,  'tax_percent' => 18, 'reorder_level' => 10, 'description' => '4-piece leather stitching awl set'],
            ['code' => 'PRD-0036', 'name' => 'Edge Beveler Tool',                  'category_id' => $catTools,     'hsn_code' => '8205', 'unit' => 'pcs', 'purchase_price' => 120,  'selling_price' => 249,  'mrp' => 299,  'tax_percent' => 18, 'reorder_level' => 10, 'description' => 'Leather edge beveler and burnisher'],
            ['code' => 'PRD-0037', 'name' => 'Industrial Sewing Machine Needle',   'category_id' => $catTools,     'hsn_code' => '7319', 'unit' => 'box', 'purchase_price' => 80,   'selling_price' => 150,  'mrp' => 180,  'tax_percent' => 18, 'reorder_level' => 20, 'description' => 'Box of 10 heavy-duty sewing machine needles'],
            // Packaging Materials (cat 8)
            ['code' => 'PRD-0038', 'name' => 'Cardboard Gift Box - Medium',        'category_id' => $catPackaging, 'hsn_code' => '4819', 'unit' => 'pcs', 'purchase_price' => 35,  'selling_price' => 65,  'mrp' => 80,  'tax_percent' => 12, 'reorder_level' => 100, 'description' => 'Branded gift box with magnetic closure'],
            ['code' => 'PRD-0039', 'name' => 'Dust Bag - Cotton',                  'category_id' => $catPackaging, 'hsn_code' => '6305', 'unit' => 'pcs', 'purchase_price' => 18,  'selling_price' => 35,  'mrp' => 45,  'tax_percent' => 5,  'reorder_level' => 200, 'description' => 'Cotton dust bag with drawstring for bags/shoes'],
            ['code' => 'PRD-0040', 'name' => 'Tissue Paper - Branded (pack of 10)', 'category_id' => $catPackaging, 'hsn_code' => '4818', 'unit' => 'box', 'purchase_price' => 25,  'selling_price' => 50,  'mrp' => 60,  'tax_percent' => 12, 'reorder_level' => 100, 'description' => 'Branded tissue wrap paper for gift packing'],
        ];

        $rows = [];
        foreach ($products as $p) {
            $p['business_id'] = $businessId;
            $p['status'] = 'active';
            $p['image'] = null;
            $p['created_by'] = $adminId;
            $p['updated_by'] = null;
            $p['deleted_by'] = null;
            $p['created_at'] = $now;
            $p['updated_at'] = $now;
            $p['deleted_at'] = null;
            $rows[] = $p;
        }

        DB::table('products')->insert($rows);
    }
}
