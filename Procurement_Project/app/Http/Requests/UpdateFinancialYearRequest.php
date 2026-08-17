<?php

namespace App\Http\Requests;

use App\Models\FinancialYear;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Http\Requests\CeoAwareFormRequest;
use Illuminate\Validation\Rule;

class UpdateFinancialYearRequest extends CeoAwareFormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }

    public function rules(): array
    {
        $financialYear = $this->route('financialYear');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('financial_years', 'name')->ignore($financialYear?->id),
            ],
            'start_date' => ['required', 'date', 'before:end_date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
