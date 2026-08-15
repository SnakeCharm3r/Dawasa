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
            'quotation_number' => ['required', 'string', 'max:50'],
            'valid_until' => ['nullable', 'date', 'after:today'],
            'status' => ['nullable', 'in:draft,active,withdrawn,expired'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_name' => ['required', 'string', 'max:255'],
            'items.*.specification' => ['nullable', 'string'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit' => ['required', 'string', 'max:50'],
            'items.*.unit_price' => ['required', 'numeric', 'gte:0'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }
}
