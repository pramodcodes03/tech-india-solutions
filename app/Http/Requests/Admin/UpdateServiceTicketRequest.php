<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'product_id' => ['nullable', 'exists:products,id'],
            'category_id' => ['nullable', 'exists:service_categories,id'],
            'issue_description' => ['required', 'string', 'max:2000'],
            'site_location' => ['nullable', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:100'],
            'contact_phone' => ['nullable', 'string', 'max:20'],
            'scheduled_at' => ['nullable', 'date'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'status' => ['nullable', 'in:open,assigned,in_progress,resolved,closed,cancelled'],
            'assigned_to' => ['nullable', 'exists:admins,id'],
            'resolution_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'Please select a customer.',
            'customer_id.exists' => 'The selected customer does not exist.',
            'product_id.exists' => 'The selected product does not exist.',
            'issue_description.required' => 'Issue description is required.',
            'issue_description.max' => 'Issue description must not exceed 2000 characters.',
            'priority.required' => 'Please select a priority level.',
            'priority.in' => 'Priority must be one of: low, medium, high, or urgent.',
            'status.in' => 'Invalid status selected.',
            'assigned_to.exists' => 'The selected assignee does not exist.',
            'resolution_notes.max' => 'Resolution notes must not exceed 2000 characters.',
        ];
    }
}
