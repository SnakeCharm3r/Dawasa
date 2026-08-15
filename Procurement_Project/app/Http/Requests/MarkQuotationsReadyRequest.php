<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MarkQuotationsReadyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole(['procurement_officer', 'super_admin']);
    }

    public function rules(): array
    {
        return [];
    }
}
