<?php

namespace App\Http\Requests\Admin;

use App\Support\Tenancy\CurrentBusiness;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpenseCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('expense_categories.edit') ?? false;
    }

    public function rules(): array
    {
        $businessId = app(CurrentBusiness::class)->id();
        $categoryId = $this->route('expense_category')?->id;

        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:100', 'alpha_dash',
                Rule::unique('expense_categories', 'slug')
                    ->where('business_id', $businessId)
                    ->ignore($categoryId)],
            'description' => ['nullable', 'string', 'max:1000'],
            'color' => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
