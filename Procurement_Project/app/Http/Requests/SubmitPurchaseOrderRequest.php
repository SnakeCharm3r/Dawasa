<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitPurchaseOrderRequest extends FormRequest
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
