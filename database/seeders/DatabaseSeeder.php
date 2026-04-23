<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            SettingsSeeder::class,
            AdminSeeder::class,
            WarehouseSeeder::class,
            CategorySeeder::class,
            ProductSeeder::class,
            CustomerSeeder::class,
            VendorSeeder::class,
            LeadSeeder::class,
            QuotationSeeder::class,
            SalesOrderSeeder::class,
            InvoiceSeeder::class,
            PaymentSeeder::class,
            PurchaseOrderSeeder::class,
            StockMovementSeeder::class,
            ServiceCategorySeeder::class,
            ServiceTicketSeeder::class,
            HrSeeder::class,
        ]);
    }
}
