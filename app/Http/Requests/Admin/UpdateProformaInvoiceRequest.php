<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProformaInvoiceRequest extends FormRequest
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
            'status' => ['in:draft,sent,accepted,rejected,expired'],
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
}
