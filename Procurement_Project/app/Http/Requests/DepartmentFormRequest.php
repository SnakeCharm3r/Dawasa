<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DepartmentFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $departmentId = $this->route('department')?->id ?? null;

        return [
            'business_entity_id' => ['required', 'exists:business_entities,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('departments')->where(function ($query) {
                    return $query->where('business_entity_id', $this->input('business_entity_id'));
                })->ignore($departmentId),
            ],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'business_entity_id.required' => 'The business entity field is required.',
            'business_entity_id.exists' => 'The selected business entity is invalid.',
            'code.unique' => 'The department code must be unique within this business entity.',
        ];
    }
}
