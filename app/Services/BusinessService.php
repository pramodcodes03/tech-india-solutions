<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Business;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BusinessService
{
    /**
     * Create a business and its initial admin atomically.
     *
     * @param  array{
     *     name: string, slug: string, legal_name?: ?string,
     *     gst?: ?string, pan?: ?string, cin?: ?string,
     *     address?: ?string, city?: ?string, state?: ?string, pincode?: ?string, country?: ?string,
     *     phone?: ?string, email?: ?string, website?: ?string,
     *     currency_code?: ?string, currency_symbol?: ?string,
     *     invoice_prefix?: ?string, quotation_prefix?: ?string, sales_order_prefix?: ?string,
     *     po_prefix?: ?string, grn_prefix?: ?string, proforma_prefix?: ?string, employee_code_prefix?: ?string,
     *     terms_and_conditions?: ?string, is_active?: bool,
     *     admin_name: string, admin_email: string, admin_password: string,
     *     logo?: ?\Illuminate\Http\UploadedFile,
     * }  $data
     */
    public function create(array $data): Business
    {
        return DB::transaction(function () use ($data) {
            $logoPath = null;
            if (! empty($data['logo'])) {
                $logoPath = $data['logo']->store('businesses/logos', 'public');
            }

            $business = Business::create([
                ...$this->businessAttributes($data),
                'logo' => $logoPath,
            ]);

            $admin = Admin::create([
                'business_id' => $business->id,
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'password' => $data['admin_password'],
                'status' => 'active',
            ]);
            $admin->assignRole('Business Admin');

            return $business;
        });
    }

    public function update(Business $business, array $data): Business
    {
        return DB::transaction(function () use ($business, $data) {
            $attrs = $this->businessAttributes($data);

            if (! empty($data['logo'])) {
                if ($business->logo) {
                    Storage::disk('public')->delete($business->logo);
                }
                $attrs['logo'] = $data['logo']->store('businesses/logos', 'public');
            }

            $business->update($attrs);

            return $business->fresh();
        });
    }

    protected function businessAttributes(array $data): array
    {
        return collect([
            'name', 'slug', 'legal_name',
            'gst', 'pan', 'cin',
            'address', 'city', 'state', 'pincode', 'country',
            'phone', 'email', 'website',
            'currency_code', 'currency_symbol',
            'invoice_prefix', 'quotation_prefix', 'sales_order_prefix',
            'po_prefix', 'grn_prefix', 'proforma_prefix', 'employee_code_prefix',
            'terms_and_conditions', 'is_active',
        ])
            ->filter(fn ($k) => array_key_exists($k, $data))
            ->mapWithKeys(fn ($k) => [$k => $data[$k]])
            ->all();
    }
}
