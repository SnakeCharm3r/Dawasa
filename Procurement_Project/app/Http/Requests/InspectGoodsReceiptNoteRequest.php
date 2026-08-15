<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InspectGoodsReceiptNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['super_admin', 'department_head']);
    }

    public function rules(): array
    {
        return [
            'inspection_comments' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'exists:goods_receipt_note_items,id'],
            'items.*.quantity_accepted' => ['required', 'numeric', 'gte:0'],
            'items.*.quantity_rejected' => ['required', 'numeric', 'gte:0'],
            'items.*.condition_status' => ['required', 'in:pending,accepted,partially_accepted,rejected,damaged'],
            'items.*.rejection_reason' => ['nullable', 'string'],
            'items.*.inspection_notes' => ['nullable', 'string'],
        ];
    }
}
