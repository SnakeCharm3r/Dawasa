<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrUpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $departmentId = $this->route('department')?->id;

        return [
            'business_entity_id' => ['required', 'exists:business_entities,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departments')->where(fn ($query) => $query->where('business_entity_id', $this->input('business_entity_id')))->ignore($departmentId),
            ],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
