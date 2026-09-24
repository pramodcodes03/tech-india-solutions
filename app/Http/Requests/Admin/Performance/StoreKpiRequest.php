<?php

namespace App\Http\Requests\Admin\Performance;

use App\Models\Kpi;
use App\Support\Tenancy\CurrentBusiness;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreKpiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;   // gated on performance_kpi.* in the controller
    }

    public function rules(): array
    {
        $kpi = $this->route('kpi');

        return [
            'kra_id' => ['required', 'exists:kras,id'],
            'code' => [
                'required', 'string', 'max:30',
                Rule::unique('kpis', 'code')
                    ->where('business_id', app(CurrentBusiness::class)->id())
                    ->ignore($kpi?->id),
            ],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'measurement_unit' => ['required', Rule::in(array_keys(Kpi::UNITS))],
            'target_value' => ['required', 'numeric', 'min:0'],
            'weightage' => ['required', 'numeric', 'min:0', 'max:100'],
            'score_formula' => ['required', Rule::in(array_keys(Kpi::FORMULAS))],
            'status' => ['required', 'in:active,inactive'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'That KPI code is already in use.',
            'kra_id.required' => 'Every KPI must sit under a KRA.',
        ];
    }
}
