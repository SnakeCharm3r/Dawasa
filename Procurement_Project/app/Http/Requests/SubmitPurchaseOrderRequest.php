<?php

namespace App\Http\Requests;

use App\Http\Requests\CeoAwareFormRequest;

class SubmitPurchaseOrderRequest extends CeoAwareFormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('procurement_officer');
    }

    public function rules(): array
    {
        return [
            'comments' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
