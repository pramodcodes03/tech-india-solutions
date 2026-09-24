<?php

namespace App\Http\Requests\Admin\Tracker;

use App\Models\VisitorLog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVisitorLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;   // gated on visitor_tracker.* in the controller
    }

    public function rules(): array
    {
        return [
            'visit_date' => ['required', 'date'],
            'visitor_name' => ['required', 'string', 'max:150'],
            'mobile' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s]{6,20}$/'],
            'source_id' => ['nullable', 'exists:tracker_options,id'],
            'purpose_id' => ['nullable', 'exists:tracker_options,id'],
            'arrival_time' => ['nullable', 'date_format:H:i'],
            'called_by' => ['nullable', 'string', 'max:120'],
            'interview_by' => ['nullable', 'string', 'max:120'],
            'availability_status' => ['required', Rule::in(array_keys(VisitorLog::STATUSES))],
            'outcome' => ['required', Rule::in(array_keys(VisitorLog::OUTCOMES))],
            'remarks' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'visitor_name.required' => 'Visitor / candidate name is required.',
            'mobile.regex' => 'Enter a valid mobile number (digits, spaces, + and - only).',
            'arrival_time.date_format' => 'Arrival time must be a valid time, e.g. 10:30.',
        ];
    }
}
