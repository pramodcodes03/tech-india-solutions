<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreProformaInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'quotation_id' => ['nullable', 'exists:quotations,id'],
            'proforma_date' => ['required', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:proforma_date'],
            'status' => ['in:draft,sent'],
            'discount_type' => ['required', 'in:percent,fixed'],
            'discount_value' => ['required', 'numeric', 'min:0'],
            'tax_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'advance_received' => ['nullable', 'numeric', 'min:0'],
            'terms' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.description' => ['required', 'string', 'max:500'],
            'items.*.hsn_code' => ['nullable', 'string', 'max:20'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit' => ['required', 'string', 'max:20'],
            'items.*.rate' => ['required', 'numeric', 'min:0'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'Please select a customer.',
            'proforma_date.required' => 'Proforma invoice date is required.',
            'valid_until.after_or_equal' => 'Valid until date must be on or after the proforma date.',
            'items.required' => 'At least one item is required.',
            'items.*.description.required' => 'Item description is required.',
            'items.*.quantity.required' => 'Item quantity is required.',
            'items.*.rate.required' => 'Item rate is required.',
        ];
    }
}
