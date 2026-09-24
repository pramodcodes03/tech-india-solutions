<?php

namespace App\Http\Requests\Admin\Tracker;

use App\Models\BreakSheet;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreBreakSheetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;   // gated on break_tracker.* in the controller
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'break_date' => ['required', 'date', 'before_or_equal:today'],
            'out_time' => ['required', 'date_format:H:i'],
            'in_time' => ['nullable', 'date_format:H:i'],
            'break_type_id' => ['nullable', 'exists:tracker_options,id'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * A break is identified by employee + date + out time; the table now holds a
     * unique key on exactly that. Catch a collision here so typing a break that
     * is already on the register comes back as a field error rather than as an
     * integrity-constraint 500.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;   // the key fields are not all trustworthy yet
            }

            $clash = BreakSheet::where('employee_id', $this->input('employee_id'))
                ->whereDate('break_date', $this->input('break_date'))
                // Stored as H:i:s, posted as H:i — compare like for like.
                ->whereTime('out_time', $this->input('out_time').':00')
                ->when($this->route('break'), fn ($q, $break) => $q->whereKeyNot($break->getKey()))
                ->exists();

            if ($clash) {
                $validator->errors()->add(
                    'out_time',
                    'This employee already has a break starting at that time on that date. Edit that entry instead.',
                );
            }
        }];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'Please choose an employee.',
            'break_date.before_or_equal' => 'A break cannot be recorded for a future date.',
            'out_time.required' => 'Out time is required.',
            'out_time.date_format' => 'Out time must be a valid time, e.g. 13:45.',
            'in_time.date_format' => 'In time must be a valid time, e.g. 14:10.',
        ];
    }
}
