<?php

namespace App\Http\Requests;

use App\Http\Requests\CeoAwareFormRequest;
use Illuminate\Validation\Rule;

class StoreOrUpdateBusinessEntityRequest extends CeoAwareFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $entityId = $this->route('business_entity')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', Rule::unique('business_entities')->ignore($entityId)],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
