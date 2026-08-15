<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MatchSupplierInvoiceRequest extends FormRequest
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
