<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use App\Http\Requests\CeoAwareFormRequest;

class RequisitionDecisionRequest extends CeoAwareFormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'comments' => ['required', 'string', 'max:2000'],
        ];
    }
}
