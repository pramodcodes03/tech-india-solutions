<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Support\Tenancy\CurrentBusiness;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Phase 1 — global, non-tenant data.
        $this->call([
            RolePermissionSeeder::class,
            SettingsSeeder::class,
            BusinessSeeder::class,
            AdminSeeder::class,
        ]);

        // Phase 2 — per-business data, seeded into every business so each one
        // looks populated when its admin logs in.
        $current = app(CurrentBusiness::class);

        foreach (Business::orderBy('id')->get() as $business) {
            $current->setWithoutSession($business);
            $this->command->info("Seeding demo data into: {$business->name}");

            $this->call([
                HrSeeder::class,
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
                EmployeeSeeder::class,
            ]);
        }

        $current->clear();
    }
}
