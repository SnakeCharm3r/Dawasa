<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierQuotationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['super_admin', 'procurement_officer']);
    }

    public function rules(): array
    {
        return [
            'purchase_requisition_id' => ['required', 'exists:purchase_requisitions,id'],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'prepared_by' => ['required', 'exists:users,id'],
            'quotation_number' => ['required', 'string', 'max:50'],
            'valid_until' => ['nullable', 'date', 'after:today'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'status' => ['nullable', 'in:draft,active,withdrawn,expired'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
