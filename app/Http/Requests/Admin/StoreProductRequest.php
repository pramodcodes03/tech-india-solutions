<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:product_categories,id'],
            'hsn_code' => ['nullable', 'string', 'max:20'],
            'unit' => ['required', 'in:pcs,kg,mtr,box'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'mrp' => ['nullable', 'numeric', 'min:0'],
            'tax_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'reorder_level' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
            'image' => ['nullable', 'image', 'max:2048', 'mimes:jpg,jpeg,png,webp'],
            'status' => ['required', 'in:active,inactive'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Product name is required.',
            'category_id.required' => 'Please select a product category.',
            'category_id.exists' => 'The selected category does not exist.',
            'unit.required' => 'Please select a unit of measurement.',
            'unit.in' => 'Unit must be one of: pcs, kg, mtr, or box.',
            'purchase_price.required' => 'Purchase price is required.',
            'purchase_price.min' => 'Purchase price cannot be negative.',
            'selling_price.required' => 'Selling price is required.',
            'selling_price.min' => 'Selling price cannot be negative.',
            'mrp.min' => 'MRP cannot be negative.',
            'tax_percent.max' => 'Tax percent cannot exceed 100.',
            'reorder_level.min' => 'Reorder level cannot be negative.',
            'image.image' => 'The file must be an image.',
            'image.max' => 'Image size must not exceed 2MB.',
            'image.mimes' => 'Image must be a JPG, JPEG, PNG, or WebP file.',
            'status.required' => 'Please select a status.',
            'status.in' => 'Status must be either active or inactive.',
        ];
    }
}
