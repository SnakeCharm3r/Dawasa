<?php

namespace App\Http\Requests;

use App\Http\Requests\CeoAwareFormRequest;

class ProformaApprovalRequest extends CeoAwareFormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'comments' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
