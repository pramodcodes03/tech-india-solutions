<?php

namespace App\Http\Requests\Admin;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpenseSubcategory;
use App\Support\Tenancy\CurrentBusiness;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('expenses.create') ?? false;
    }

    protected function prepareForValidation(): void
    {
        // Coerce empty string / "null" string to actual null so the
        // 'nullable' rule applies before 'integer' kicks in.
        if (in_array($this->input('expense_subcategory_id'), ['', 'null', null], true)) {
            $this->merge(['expense_subcategory_id' => null]);
        }
    }

    public function rules(): array
    {
        $businessId = app(CurrentBusiness::class)->id();

        return [
            'expense_category_id' => ['required', 'integer',
                Rule::exists('expense_categories', 'id')->where('business_id', $businessId)],
            'expense_subcategory_id' => ['nullable', 'integer',
                Rule::exists('expense_subcategories', 'id')
                    ->where('business_id', $businessId)
                    ->where('expense_category_id', $this->input('expense_category_id'))],
            'type' => ['required', Rule::in([Expense::TYPE_RECURRING, Expense::TYPE_ONE_OFF])],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'amount' => ['required', 'numeric', 'min:0'],
            'expense_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],

            // Recurring-only
            'due_day_of_month' => ['nullable', 'required_if:type,recurring', 'integer', 'between:1,28'],

            'payment_method' => ['nullable', 'string', 'max:50'],
            'payment_reference' => ['nullable', 'string', 'max:120'],
            'attachment' => ['nullable', 'file', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'due_day_of_month.required_if' => 'Day of month is required for recurring expenses.',
            'due_day_of_month.between' => 'Day of month must be between 1 and 28.',
        ];
    }
}
