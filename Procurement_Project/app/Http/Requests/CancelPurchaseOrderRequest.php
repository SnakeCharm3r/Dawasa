<?php

namespace App\Http\Requests;

use App\Http\Requests\CeoAwareFormRequest;

class CancelPurchaseOrderRequest extends CeoAwareFormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['procurement_officer', 'super_admin']);
    }

    public function rules(): array
    {
        return [
            'cancellation_reason' => ['required', 'string', 'max:2000'],
        ];
    }
}
