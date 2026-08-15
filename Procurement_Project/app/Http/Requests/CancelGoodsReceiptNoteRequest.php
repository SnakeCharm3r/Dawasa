<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelGoodsReceiptNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['super_admin', 'procurement_officer', 'storekeeper', 'receiving_officer']);
    }

    public function rules(): array
    {
        return [
            'cancellation_reason' => ['required', 'string', 'max:2000'],
        ];
    }
}
