<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreGoodsReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'purchase_order_id' => ['required', 'exists:purchase_orders,id'],
            'received_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.purchase_order_item_id' => ['required', 'exists:purchase_order_items,id'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity_received' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function messages(): array
    {
        return [
            'purchase_order_id.required' => 'Please select a purchase order.',
            'purchase_order_id.exists' => 'The selected purchase order does not exist.',
            'received_date.required' => 'Received date is required.',
            'items.required' => 'At least one item is required.',
            'items.min' => 'At least one item is required.',
            'items.*.purchase_order_item_id.required' => 'Purchase order item reference is required.',
            'items.*.purchase_order_item_id.exists' => 'The referenced purchase order item does not exist.',
            'items.*.product_id.required' => 'Product is required for each item.',
            'items.*.product_id.exists' => 'The selected product does not exist.',
            'items.*.quantity_received.required' => 'Received quantity is required.',
            'items.*.quantity_received.min' => 'Received quantity must be at least 0.01.',
        ];
    }
}
