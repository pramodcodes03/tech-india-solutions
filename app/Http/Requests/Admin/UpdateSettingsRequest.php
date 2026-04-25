<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'settings' => ['required', 'array'],
            'settings.company_name' => ['nullable', 'string', 'max:255'],
            'settings.company_address' => ['nullable', 'string', 'max:500'],
            'settings.company_gst' => ['nullable', 'string', 'max:20'],
            'settings.company_pan' => ['nullable', 'string', 'max:20'],
            'settings.company_phone' => ['nullable', 'string', 'max:20'],
            'settings.company_email' => ['nullable', 'email'],
            'settings.company_logo' => ['nullable', 'string', 'max:255'],
            'settings.bank_name' => ['nullable', 'string', 'max:120'],
            'settings.bank_account' => ['nullable', 'string', 'max:40'],
            'settings.bank_ifsc' => ['nullable', 'string', 'max:20'],
            'settings.bank_account_type' => ['nullable', 'string', 'max:20'],
            'settings.bank_account_holder' => ['nullable', 'string', 'max:120'],
            'settings.bank_branch' => ['nullable', 'string', 'max:120'],
            'settings.invoice_prefix' => ['nullable', 'string', 'max:10'],
            'settings.quotation_prefix' => ['nullable', 'string', 'max:10'],
            'settings.currency_symbol' => ['nullable', 'string', 'max:5'],
            'settings.terms_and_conditions' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'settings.required' => 'Settings data is required.',
            'settings.array' => 'Settings must be provided as an array.',
            'settings.company_email.email' => 'Please enter a valid company email address.',
            'settings.company_name.max' => 'Company name must not exceed 255 characters.',
            'settings.company_address.max' => 'Company address must not exceed 500 characters.',
        ];
    }
}
