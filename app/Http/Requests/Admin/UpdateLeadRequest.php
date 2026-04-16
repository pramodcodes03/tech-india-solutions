<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email'],
            'source' => ['required', 'in:website,referral,walk-in,other'],
            'status' => ['required', 'in:new,contacted,qualified,proposal,won,lost'],
            'assigned_to' => ['nullable', 'exists:admins,id'],
            'expected_value' => ['nullable', 'numeric', 'min:0'],
            'next_follow_up_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Lead name is required.',
            'source.required' => 'Please select the lead source.',
            'source.in' => 'Lead source must be one of: website, referral, walk-in, or other.',
            'status.required' => 'Please select the lead status.',
            'status.in' => 'Invalid lead status selected.',
            'assigned_to.exists' => 'The selected assignee does not exist.',
            'expected_value.min' => 'Expected value cannot be negative.',
        ];
    }
}
