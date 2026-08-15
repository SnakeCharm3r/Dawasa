<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseOrderConfirmationRequest extends FormRequest
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
