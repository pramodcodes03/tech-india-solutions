<?php

namespace App\Http\Requests\Admin\Tracker;

use App\Support\Tenancy\CurrentBusiness;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDieselEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;   // gated on diesel_tracker.* in the controller
    }

    public function rules(): array
    {
        $entry = $this->route('diesel');

        return [
            'serial_no' => [
                'required', 'string', 'max:40',
                // Serial numbers are unique per business; the tenancy scope is
                // not applied to the raw rule, so scope it explicitly.
                Rule::unique('diesel_entries', 'serial_no')
                    ->where('business_id', app(CurrentBusiness::class)->id())
                    ->ignore($entry?->id),
            ],
            'entry_date' => ['required', 'date', 'before_or_equal:today'],
            'entry_time' => ['nullable', 'date_format:H:i'],
            'bill_no' => ['nullable', 'string', 'max:60'],
            'slip_no' => ['nullable', 'string', 'max:60'],
            'vehicle_no' => ['nullable', 'string', 'max:40'],
            'quantity' => ['required', 'numeric', 'min:0.01', 'max:99999'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'remarks' => ['nullable', 'string', 'max:500'],
            'remove_attachment' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'serial_no.unique' => 'That serial number is already used by another diesel entry.',
            'entry_date.before_or_equal' => 'A diesel entry cannot be dated in the future.',
            'quantity.min' => 'Quantity must be greater than zero.',
            'amount.min' => 'Amount must be greater than zero.',
            'attachment.mimes' => 'The receipt / slip must be a PDF, JPG or PNG file.',
            'attachment.max' => 'The receipt / slip may not be larger than 5 MB.',
        ];
    }
}
