<?php

namespace App\Http\Requests;

use App\Models\EntityBudget;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Http\Requests\CeoAwareFormRequest;
use Illuminate\Validation\ValidationException;

class ApproveEntityBudgetRequest extends CeoAwareFormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole(['super_admin', 'gm']);
    }

    public function rules(): array
    {
        return [
            'approved_amount' => ['required', 'numeric', 'min:0.01'],
            'comments' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $entityBudget = $this->route('entityBudget');

            if ($entityBudget instanceof EntityBudget) {
                $approvedAmount = $this->input('approved_amount');

                if ($approvedAmount > $entityBudget->proposed_amount && ! $this->filled('comments')) {
                    $validator->errors()->add('comments', 'Comments are required when approved amount differs from the proposed amount.');
                }

                if (auth()->id() === $entityBudget->proposed_by) {
                    $validator->errors()->add('approved_amount', 'You cannot approve a budget that you proposed.');
                }
            }
        });
    }
}
