<?php

namespace App\Http\Requests;

use App\Http\Requests\CeoAwareFormRequest;

class UpdateGoodsReceiptNoteRequest extends CeoAwareFormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['super_admin', 'procurement_officer', 'storekeeper', 'receiving_officer']);
    }

    public function rules(): array
    {
        return [
            'received_date' => ['nullable', 'date'],
            'delivery_note_number' => ['nullable', 'string', 'max:50'],
            'supplier_invoice_reference' => ['nullable', 'string', 'max:50'],
            'delivery_condition' => ['nullable', 'in:good,damaged,partial,rejected'],
            'inspection_required' => ['boolean'],
            'received_location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.id' => ['required', 'exists:goods_receipt_note_items,id'],
            'items.*.quantity_received' => ['required', 'numeric', 'gt:0'],
        ];
    }
}
