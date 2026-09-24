<?php

namespace App\Http\Requests\Admin\Performance;

use App\Models\PerformanceCycle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;   // gated on performance.configure in the controller
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'frequency' => ['required', Rule::in(array_keys(PerformanceCycle::FREQUENCIES))],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            // Due dates are optional, but a review cannot be due before the
            // period it reviews has ended.
            'self_review_due' => ['nullable', 'date', 'after_or_equal:period_start'],
            'manager_review_due' => ['nullable', 'date', 'after_or_equal:self_review_due'],
            'hr_review_due' => ['nullable', 'date', 'after_or_equal:manager_review_due'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'period_end.after_or_equal' => 'The period must end on or after it starts.',
            'manager_review_due.after_or_equal' => 'Manager review cannot be due before the self-assessment is.',
            'hr_review_due.after_or_equal' => 'HR review cannot be due before the manager review is.',
        ];
    }
}
