<?php

namespace App\Http\Requests;

use App\Models\BusinessEntity;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Http\Requests\CeoAwareFormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends CeoAwareFormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
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

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $entityId = $this->input('business_entity_id');

            if ($entityId && ! BusinessEntity::where('id', $entityId)->where('is_active', true)->exists()) {
                $validator->errors()->add('business_entity_id', 'The selected business entity must be active.');
            }
        });
    }
}
