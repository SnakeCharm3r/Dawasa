<?php

namespace App\Http\Requests;

use App\Http\Requests\CeoAwareFormRequest;

class PurchaseOrderConfirmationRequest extends CeoAwareFormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('accountant');
    }

    public function rules(): array
    {
        return [
            'comments' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
