<?php

namespace App\Http\Requests;

use App\Http\Requests\CeoAwareFormRequest;

class RecordPaymentRequest extends CeoAwareFormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['super_admin', 'accountant']);
    }

    public function rules(): array
    {
        return [
            'payment_date' => ['required', 'date'],
            'payment_reference' => ['required', 'string', 'max:50'],
            'amount_paid' => ['required', 'numeric', 'gt:0'],
            'payment_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
