<?php

namespace App\Http\Requests;

use App\Http\Requests\CeoAwareFormRequest;

class PaymentVoucherDecisionRequest extends CeoAwareFormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['super_admin', 'gm']);
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', 'in:approve,return,reject'],
            'reason' => ['required_if:decision,return,reject', 'string', 'max:2000'],
            'comments' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
