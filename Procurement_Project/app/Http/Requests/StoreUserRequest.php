<?php

namespace App\Http\Requests;

use App\Models\Department;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Http\Requests\CeoAwareFormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends CeoAwareFormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'department_id' => ['required', 'exists:departments,id'],
            'line_manager_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('is_active', true)],
            'job_title' => ['required', 'string', 'max:255'],
            'is_line_manager' => ['required', 'boolean'],
            'phone' => ['nullable', 'string', 'max:50'],
            'is_active' => ['required', 'boolean'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', 'string', Rule::exists('roles', 'name')],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $departmentId = $this->input('department_id');
            $lineManagerId = $this->input('line_manager_id');
            $isLineManager = $this->boolean('is_line_manager');

            if ($departmentId && ! Department::where('id', $departmentId)->whereHas('businessEntity', fn ($query) => $query->where('is_active', true))->exists()) {
                $validator->errors()->add('department_id', 'The selected department must belong to an active business entity.');
            }

            if ($isLineManager && $lineManagerId) {
                $validator->errors()->add('line_manager_id', 'A line manager must not have a reporting line.');
            }

            if ($lineManagerId) {
                $lineManager = User::find($lineManagerId);

                if ($lineManager && $departmentId && $lineManager->department_id !== $departmentId) {
                    $validator->errors()->add('line_manager_id', 'The selected line manager must belong to the same department.');
                }

                if ($lineManager && (! $lineManager->is_line_manager || ! $lineManager->hasAnyRole(['line_manager', 'department_head']))) {
                    $validator->errors()->add('line_manager_id', 'The selected user must be an active line manager.');
                }
            }
        });
    }
}
