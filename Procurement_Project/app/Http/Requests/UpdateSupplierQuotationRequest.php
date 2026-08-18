<?php

namespace App\Http\Requests;

class UpdateSupplierQuotationRequest extends CeoAwareFormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['super_admin', 'procurement_officer']);
    }

    public function rules(): array
    {
        return [
            'valid_until' => ['nullable', 'date', 'after:today'],
            'status' => ['nullable', 'in:draft,active,withdrawn,expired'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
