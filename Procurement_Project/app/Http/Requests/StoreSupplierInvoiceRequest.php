<?php

namespace App\Http\Requests;

use App\Http\Requests\CeoAwareFormRequest;

class StoreSupplierInvoiceRequest extends CeoAwareFormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['super_admin', 'accountant']);
    }

    public function rules(): array
    {
        return [
            'invoice_number' => ['required', 'string', 'max:50'],
            'purchase_order_id' => ['required', 'exists:purchase_orders,id'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after:invoice_date'],
            'received_date' => ['required', 'date'],
            'currency' => ['nullable', 'string', 'max:10'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.purchase_order_item_id' => ['required', 'exists:purchase_order_items,id'],
            'items.*.quantity_invoiced' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'gt:0'],
        ];
    }
}
