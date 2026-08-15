<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvoiceVarianceDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['super_admin', 'gm']);
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', 'in:approve,return'],
            'reason' => ['required_if:decision,return', 'string', 'max:2000'],
            'comments' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
