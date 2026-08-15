<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentVoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['super_admin', 'accountant']);
    }

    public function rules(): array
    {
        return [
            'payment_date' => ['nullable', 'date'],
            'payment_method' => ['nullable', 'in:bank_transfer,cheque,cash,mobile_money,other'],
            'payment_reference' => ['nullable', 'string', 'max:50'],
            'amount_requested' => ['nullable', 'numeric', 'gt:0'],
            'comments' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
