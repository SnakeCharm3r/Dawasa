<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Http\Requests\CeoAwareFormRequest;
use Illuminate\Validation\Rule;

class UserFormRequest extends CeoAwareFormRequest
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
        $userId = $this->route('user')?->id ?? null;

        return [
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'department_id' => ['required', 'exists:departments,id'],
            'line_manager_id' => [
                'nullable',
                'exists:users,id',
                function ($attribute, $value, $fail) use ($userId) {
                    if ($value === null) {
                        return;
                    }

                    // Cannot be own line manager
                    if ($value == $userId) {
                        $fail('A user cannot be their own line manager.');
                        return;
                    }

                    // If is_line_manager is true, line_manager_id must be null
                    if ($this->boolean('is_line_manager')) {
                        $fail('A line manager cannot have another line manager.');
                        return;
                    }

                    // Line manager must belong to the same department
                    $lineManager = User::find($value);
                    if ($lineManager && $lineManager->department_id != $this->input('department_id')) {
                        $fail('The line manager must belong to the same department.');
                    }
                },
            ],
            'job_title' => ['required', 'string', 'max:255'],
            'is_line_manager' => ['boolean'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($userId)],
            'phone' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
            'password' => $userId ? ['nullable', 'string', 'min:8'] : ['required', 'string', 'min:8'],
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
            'department_id.required' => 'The department field is required.',
            'department_id.exists' => 'The selected department is invalid.',
            'line_manager_id.exists' => 'The selected line manager is invalid.',
        ];
    }
}
