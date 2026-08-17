<?php

namespace App\Http\Requests;

use App\Models\EntityBudget;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Http\Requests\CeoAwareFormRequest;
use Illuminate\Validation\Rule;

class UpdateEntityBudgetRequest extends CeoAwareFormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('accountant');
    }

    public function rules(): array
    {
        $entityBudget = $this->route('entityBudget');

        return [
            'business_entity_id' => [
                'required',
                'integer',
                'exists:business_entities,id',
                Rule::unique('entity_budgets')->where(function ($query) {
                    return $query->where('financial_year_id', $this->input('financial_year_id'));
                })->ignore($entityBudget?->id),
            ],
            'financial_year_id' => ['required', 'integer', 'exists:financial_years,id'],
            'proposed_amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
