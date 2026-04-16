<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'invoice_id' => ['required', 'exists:invoices,id'],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'mode' => ['required', 'in:cash,cheque,bank_transfer,upi,card'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'invoice_id.required' => 'Please select an invoice.',
            'invoice_id.exists' => 'The selected invoice does not exist.',
            'payment_date.required' => 'Payment date is required.',
            'amount.required' => 'Payment amount is required.',
            'amount.min' => 'Payment amount must be at least 0.01.',
            'mode.required' => 'Please select a payment mode.',
            'mode.in' => 'Payment mode must be one of: cash, cheque, bank transfer, UPI, or card.',
        ];
    }
}
