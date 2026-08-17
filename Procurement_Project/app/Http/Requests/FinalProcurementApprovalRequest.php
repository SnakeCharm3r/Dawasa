<?php

namespace App\Http\Requests;

use App\Http\Requests\CeoAwareFormRequest;

class FinalProcurementApprovalRequest extends CeoAwareFormRequest
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
