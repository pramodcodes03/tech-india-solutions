<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $adminId = Admin::first()->id;
        $now = now();

        $categories = [
            ['name' => 'Leather Goods',       'slug' => 'leather-goods',       'description' => 'Finished leather products including belts, wallets, and accessories'],
            ['name' => 'Apparel',              'slug' => 'apparel',             'description' => 'Clothing items including shirts, jackets, and trousers'],
            ['name' => 'Accessories',          'slug' => 'accessories',         'description' => 'Fashion accessories including scarves, gloves, and keychains'],
            ['name' => 'Footwear',             'slug' => 'footwear',            'description' => 'Shoes, sandals, boots, and other footwear items'],
            ['name' => 'Bags & Luggage',       'slug' => 'bags-luggage',        'description' => 'Handbags, travel bags, laptop bags, and luggage'],
            ['name' => 'Raw Materials',        'slug' => 'raw-materials',       'description' => 'Leather hides, fabrics, threads, and other raw materials'],
            ['name' => 'Tools & Equipment',    'slug' => 'tools-equipment',     'description' => 'Manufacturing tools, cutting machines, and stitching equipment'],
            ['name' => 'Packaging Materials',  'slug' => 'packaging-materials', 'description' => 'Boxes, wrapping, labels, and packaging supplies'],
        ];

        foreach ($categories as $i => $cat) {
            $cat['parent_id'] = null;
            $cat['is_active'] = true;
            $cat['sort_order'] = $i + 1;
            $cat['created_by'] = $adminId;
            $cat['updated_by'] = null;
            $cat['deleted_by'] = null;
            $cat['created_at'] = $now;
            $cat['updated_at'] = $now;
            $cat['deleted_at'] = null;

            DB::table('product_categories')->insert($cat);
        }
    }
}
