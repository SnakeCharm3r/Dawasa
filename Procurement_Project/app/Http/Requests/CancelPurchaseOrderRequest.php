<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelPurchaseOrderRequest extends FormRequest
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
