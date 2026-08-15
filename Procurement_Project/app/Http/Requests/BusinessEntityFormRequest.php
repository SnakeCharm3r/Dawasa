<?php

namespace App\Http\Requests;

use App\Models\Department;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BusinessEntityFormRequest extends FormRequest
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
        $businessEntityId = $this->route('business_entity')?->id ?? null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('business_entities')->ignore($businessEntityId),
            ],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $businessEntityId = $this->route('business_entity')?->id ?? null;

            // Prevent deleting an entity if it still has departments
            if ($this->isMethod('delete') && $businessEntityId) {
                $hasDepartments = Department::where('business_entity_id', $businessEntityId)->exists();
                if ($hasDepartments) {
                    $validator->errors()->add('business_entity', 'Cannot delete this business entity because it has associated departments.');
                }
            }
        });
    }
}
