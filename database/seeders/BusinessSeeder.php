<?php

namespace Database\Seeders;

use App\Models\Business;
use Illuminate\Database\Seeder;

class BusinessSeeder extends Seeder
{
    public function run(): void
    {
        $businesses = [
            [
                'slug' => 'altechnics',
                'name' => 'Tech India Solutions Pvt Ltd',
                'legal_name' => 'Tech India Solutions Pvt Ltd',
                'gst' => '33AAACA1234F1Z5',
                'pan' => 'AAACA1234F',
                'address' => 'No. 12, Industrial Estate, Ambattur',
                'city' => 'Chennai',
                'state' => 'Tamil Nadu',
                'pincode' => '600058',
                'phone' => '+91 44 2625 1234',
                'email' => 'contact@altechnics.com',
                'website' => 'https://altechnics.com',
                'invoice_prefix' => 'ALT-INV-',
                'quotation_prefix' => 'ALT-QUO-',
                'sales_order_prefix' => 'ALT-SO-',
                'po_prefix' => 'ALT-PO-',
                'grn_prefix' => 'ALT-GRN-',
                'proforma_prefix' => 'ALT-PI-',
                'employee_code_prefix' => 'ALT-',
            ],
            [
                'slug' => 'al-engineering',
                'name' => 'AL Engineering Works',
                'legal_name' => 'AL Engineering Works LLP',
                'gst' => '27BBBCA5678G1Z9',
                'pan' => 'BBBCA5678G',
                'address' => 'Plot 45, MIDC, Andheri East',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'pincode' => '400093',
                'phone' => '+91 22 2825 6789',
                'email' => 'info@al-engineering.in',
                'invoice_prefix' => 'ALE-INV-',
                'quotation_prefix' => 'ALE-QUO-',
                'sales_order_prefix' => 'ALE-SO-',
                'po_prefix' => 'ALE-PO-',
                'grn_prefix' => 'ALE-GRN-',
                'proforma_prefix' => 'ALE-PI-',
                'employee_code_prefix' => 'ALE-',
            ],
            [
                'slug' => 'al-retail',
                'name' => 'AL Retail',
                'legal_name' => 'AL Retail Pvt. Ltd.',
                'gst' => '29CCCCA9012H1Z2',
                'pan' => 'CCCCA9012H',
                'address' => 'Shop 7, Brigade Road',
                'city' => 'Bengaluru',
                'state' => 'Karnataka',
                'pincode' => '560001',
                'phone' => '+91 80 4112 3456',
                'email' => 'hello@alretail.in',
                'invoice_prefix' => 'ALR-INV-',
                'quotation_prefix' => 'ALR-QUO-',
                'sales_order_prefix' => 'ALR-SO-',
                'po_prefix' => 'ALR-PO-',
                'grn_prefix' => 'ALR-GRN-',
                'proforma_prefix' => 'ALR-PI-',
                'employee_code_prefix' => 'ALR-',
            ],
            [
                'slug' => 'al-services',
                'name' => 'AL Services',
                'legal_name' => 'AL Services Pvt. Ltd.',
                'gst' => '07DDDCA3456J1Z6',
                'pan' => 'DDDCA3456J',
                'address' => '21, Green Park Extension',
                'city' => 'New Delhi',
                'state' => 'Delhi',
                'pincode' => '110016',
                'phone' => '+91 11 2685 1234',
                'email' => 'support@alservices.in',
                'invoice_prefix' => 'ALS-INV-',
                'quotation_prefix' => 'ALS-QUO-',
                'sales_order_prefix' => 'ALS-SO-',
                'po_prefix' => 'ALS-PO-',
                'grn_prefix' => 'ALS-GRN-',
                'proforma_prefix' => 'ALS-PI-',
                'employee_code_prefix' => 'ALS-',
            ],
            [
                'slug' => 'al-exports',
                'name' => 'AL Exports',
                'legal_name' => 'AL Exports & Trading Co.',
                'gst' => '24EEECA7890K1Z3',
                'pan' => 'EEECA7890K',
                'address' => '8-2-293, Banjara Hills',
                'city' => 'Hyderabad',
                'state' => 'Telangana',
                'pincode' => '500034',
                'phone' => '+91 40 2336 7890',
                'email' => 'trade@alexports.in',
                'invoice_prefix' => 'ALX-INV-',
                'quotation_prefix' => 'ALX-QUO-',
                'sales_order_prefix' => 'ALX-SO-',
                'po_prefix' => 'ALX-PO-',
                'grn_prefix' => 'ALX-GRN-',
                'proforma_prefix' => 'ALX-PI-',
                'employee_code_prefix' => 'ALX-',
            ],
        ];

        foreach ($businesses as $data) {
            Business::firstOrCreate(['slug' => $data['slug']], $data + ['is_active' => true]);
        }
    }
}
