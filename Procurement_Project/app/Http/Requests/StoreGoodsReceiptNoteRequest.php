<?php

namespace App\Http\Requests;

use App\Http\Requests\CeoAwareFormRequest;

class StoreGoodsReceiptNoteRequest extends CeoAwareFormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['super_admin', 'procurement_officer', 'storekeeper', 'receiving_officer']);
    }

    public function rules(): array
    {
        return [
            'purchase_order_id' => ['required', 'exists:purchase_orders,id'],
            'received_date' => ['required', 'date'],
            'delivery_note_number' => ['required', 'string', 'max:50'],
            'supplier_invoice_reference' => ['nullable', 'string', 'max:50'],
            'delivery_condition' => ['nullable', 'in:good,damaged,partial,rejected'],
            'inspection_required' => ['boolean'],
            'received_location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.purchase_order_item_id' => ['required', 'exists:purchase_order_items,id'],
            'items.*.quantity_received' => ['required', 'numeric', 'gt:0'],
        ];
    }
}
