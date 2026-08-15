<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierQuotationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['super_admin', 'procurement_officer']);
    }

    public function rules(): array
    {
        return [
            'quotation_number' => ['nullable', 'string', 'max:50'],
            'valid_until' => ['nullable', 'date', 'after:today'],
            'total_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'in:draft,active,withdrawn,expired'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
