<?php

namespace App\Http\Requests;

use App\Http\Requests\CeoAwareFormRequest;

class MarkQuotationsReadyRequest extends CeoAwareFormRequest
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
