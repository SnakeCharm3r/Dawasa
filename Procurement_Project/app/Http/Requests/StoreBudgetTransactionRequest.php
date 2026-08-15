<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBudgetTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole(['super_admin', 'accountant']);
    }

    public function rules(): array
    {
        return [
            'transaction_type' => [
                'required',
                'string',
                Rule::in([
                    'commitment',
                    'commitment_release',
                    'expenditure',
                    'expenditure_reversal',
                    'adjustment',
                ]),
            ],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reference_type' => ['nullable', 'string', 'max:255'],
            'reference_id' => ['nullable', 'integer'],
            'description' => ['nullable', 'string', 'max:2000'],
            'transaction_date' => ['nullable', 'date'],
        ];
    }
}
