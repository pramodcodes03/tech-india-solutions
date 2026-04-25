<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->boolean('whatsapp_same_as_mobile')) {
            $this->merge(['whatsapp_number' => $this->input('phone')]);
        }
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email', Rule::unique('employees', 'email')],
            'personal_email' => ['nullable', 'email', 'max:191'],
            'password' => ['nullable', 'string', 'min:6'],
            'phone' => ['nullable', 'string', 'max:20'],
            'alt_phone' => ['nullable', 'string', 'max:20'],
            'whatsapp_number' => ['nullable', 'string', 'max:20'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'marital_status' => ['nullable', Rule::in(['single', 'married', 'divorced', 'widowed'])],
            'blood_group' => ['nullable', 'string', 'max:5'],

            'current_address' => ['nullable', 'string'],
            'permanent_address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'pincode' => ['nullable', 'string', 'max:10'],
            'country' => ['nullable', 'string', 'max:100'],

            'department_id' => ['nullable', 'exists:departments,id'],
            'designation_id' => ['nullable', 'exists:designations,id'],
            'shift_id' => ['nullable', 'exists:shifts,id'],
            'reporting_manager_id' => ['nullable', 'exists:employees,id'],
            'joining_date' => ['required', 'date'],
            'probation_end_date' => ['nullable', 'date', 'after_or_equal:joining_date'],
            'confirmation_date' => ['nullable', 'date', 'after_or_equal:joining_date'],
            'employment_type' => ['required', Rule::in(['full_time', 'part_time', 'contract', 'intern'])],
            'work_mode' => ['required', Rule::in(['on_site', 'remote', 'hybrid'])],

            'pan_number' => ['nullable', 'string', 'max:20'],
            'aadhar_number' => ['nullable', 'string', 'max:20'],
            'pf_number' => ['nullable', 'string', 'max:30'],
            'uan_number' => ['nullable', 'string', 'max:30'],
            'esi_number' => ['nullable', 'string', 'max:30'],

            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:30'],
            'bank_ifsc' => ['nullable', 'string', 'max:20'],
            'bank_branch' => ['nullable', 'string', 'max:100'],

            'emergency_contact_name' => ['nullable', 'string', 'max:100'],
            'emergency_contact_relation' => ['nullable', 'string', 'max:50'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],

            'bgv_status' => ['nullable', Rule::in(['pending', 'in_progress', 'cleared', 'failed'])],
            'status' => ['nullable', Rule::in(['active', 'probation', 'on_notice', 'terminated', 'resigned', 'absconded', 'inactive'])],
        ];
    }
}
