<?php

namespace App\Http\Requests;

use App\Models\Department;
use App\Models\User;
use App\Http\Requests\CeoAwareFormRequest;
use Illuminate\Validation\Rule;

class StoreOrUpdateUserRequest extends CeoAwareFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($userId)],
            'department_id' => ['required', 'exists:departments,id'],
            'line_manager_id' => [
                'nullable',
                'different:id',
                'exists:users,id',
            ],
            'job_title' => ['required', 'string', 'max:255'],
            'is_line_manager' => ['required', 'boolean'],
            'phone' => ['nullable', 'string', 'max:50'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $data = $this->validated();
            $departmentId = $data['department_id'] ?? null;
            $lineManagerId = $data['line_manager_id'] ?? null;
            $isLineManager = $data['is_line_manager'] ?? false;

            if ($isLineManager && $lineManagerId) {
                $validator->errors()->add('line_manager_id', 'A line manager cannot have a line manager.');
            }

            if ($lineManagerId) {
                $lineManager = User::find($lineManagerId);
                if (! $lineManager) {
                    return;
                }

                if ($lineManager->id === $this->route('user')?->id) {
                    $validator->errors()->add('line_manager_id', 'A user cannot be their own line manager.');
                }

                if ($departmentId && $lineManager->department_id !== $departmentId) {
                    $validator->errors()->add('line_manager_id', 'The selected line manager must belong to the same department.');
                }

                if (! $lineManager->is_active || ! $lineManager->is_line_manager || ! $lineManager->hasAnyRole(['line_manager', 'department_head'])) {
                    $validator->errors()->add('line_manager_id', 'The selected user must be an active line manager.');
                }
            }
        });
    }
}
