<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreBusinessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::guard('admin')->user()?->isSuperAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:64', 'alpha_dash', 'unique:businesses,slug'],
            'legal_name' => ['nullable', 'string', 'max:255'],

            'gst' => ['nullable', 'string', 'max:30'],
            'pan' => ['nullable', 'string', 'max:20'],
            'cin' => ['nullable', 'string', 'max:30'],

            'address' => ['nullable', 'string', 'max:1000'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'pincode' => ['nullable', 'string', 'max:10'],
            'country' => ['nullable', 'string', 'max:100'],

            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],

            'logo' => ['nullable', 'image', 'max:2048'],

            'currency_code' => ['nullable', 'string', 'max:5'],
            'currency_symbol' => ['nullable', 'string', 'max:5'],

            'invoice_prefix' => ['nullable', 'string', 'max:20'],
            'quotation_prefix' => ['nullable', 'string', 'max:20'],
            'sales_order_prefix' => ['nullable', 'string', 'max:20'],
            'po_prefix' => ['nullable', 'string', 'max:20'],
            'grn_prefix' => ['nullable', 'string', 'max:20'],
            'proforma_prefix' => ['nullable', 'string', 'max:20'],
            'employee_code_prefix' => ['nullable', 'string', 'max:20'],

            'terms_and_conditions' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],

            // Initial admin (created together with the business)
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'unique:admins,email'],
            'admin_password' => ['required', 'string', 'min:8'],
        ];
    }
}
