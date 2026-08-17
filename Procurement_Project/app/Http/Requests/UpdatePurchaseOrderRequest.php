<?php

namespace App\Http\Requests;

use App\Http\Requests\CeoAwareFormRequest;

class UpdatePurchaseOrderRequest extends CeoAwareFormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('procurement_officer');
    }

    public function rules(): array
    {
        return [
            'order_date' => ['nullable', 'date'],
            'expected_delivery_date' => ['nullable', 'date'],
            'currency' => ['nullable', 'string', 'max:10'],
            'payment_terms' => ['nullable', 'string'],
            'delivery_terms' => ['nullable', 'string'],
            'delivery_address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
