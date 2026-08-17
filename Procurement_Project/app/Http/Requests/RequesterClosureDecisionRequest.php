<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use App\Http\Requests\CeoAwareFormRequest;

class RequesterClosureDecisionRequest extends CeoAwareFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'decision' => 'required|in:confirm,return',
            'comments' => 'nullable|string|max:1000',
            'reason' => 'required_if:decision,return|string|max:1000',
        ];
    }
}
