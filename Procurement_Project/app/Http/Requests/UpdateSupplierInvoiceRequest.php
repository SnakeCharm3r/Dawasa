<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['super_admin', 'accountant']);
    }

    public function rules(): array
    {
        return [
            'invoice_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after:invoice_date'],
            'currency' => ['nullable', 'string', 'max:10'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.id' => ['required', 'exists:supplier_invoice_items,id'],
            'items.*.quantity_invoiced' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'gt:0'],
        ];
    }
}
