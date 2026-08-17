<?php

namespace App\Http\Requests;

use App\Http\Requests\CeoAwareFormRequest;

class SubmitPaymentVoucherRequest extends CeoAwareFormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['super_admin', 'accountant']);
    }

    public function rules(): array
    {
        return [
            'comments' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
