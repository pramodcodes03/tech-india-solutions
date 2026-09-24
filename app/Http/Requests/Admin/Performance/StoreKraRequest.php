<?php

namespace App\Http\Requests\Admin\Performance;

use App\Models\PerformanceCycle;
use App\Support\Tenancy\CurrentBusiness;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreKraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;   // gated on performance_kra.* in the controller
    }

    public function rules(): array
    {
        $kra = $this->route('kra');

        return [
            'code' => [
                'required', 'string', 'max:30',
                Rule::unique('kras', 'code')
                    ->where('business_id', app(CurrentBusiness::class)->id())
                    ->ignore($kra?->id),
            ],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'designation_id' => ['nullable', 'exists:designations,id'],
            'weightage' => ['required', 'numeric', 'min:0', 'max:100'],
            'review_frequency' => ['required', Rule::in(array_keys(PerformanceCycle::FREQUENCIES))],
            'manager_id' => ['nullable', 'exists:employees,id'],
            'status' => ['required', 'in:active,inactive'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'That KRA code is already in use.',
            'weightage.max' => 'A single KRA cannot be worth more than 100%.',
            'end_date.after_or_equal' => 'The end date must be on or after the start date.',
        ];
    }
}
