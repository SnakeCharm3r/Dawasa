<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBusinessEntityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }

    public function rules(): array
    {
        $entityId = $this->route('businessEntity')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', Rule::unique('business_entities', 'code')->ignore($entityId)],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
