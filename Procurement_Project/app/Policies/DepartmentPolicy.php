<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;

class DepartmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['super_admin', 'gm']);
    }

    public function view(User $user, Department $department): bool
    {
        return $user->hasRole(['super_admin', 'gm']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    public function update(User $user, Department $department): bool
    {
        return $user->hasRole('super_admin');
    }

    public function delete(User $user, Department $department): bool
    {
        return $user->hasRole('super_admin');
    }

    public function restore(User $user, Department $department): bool
    {
        return false;
    }

    public function forceDelete(User $user, Department $department): bool
    {
        return false;
    }
}
