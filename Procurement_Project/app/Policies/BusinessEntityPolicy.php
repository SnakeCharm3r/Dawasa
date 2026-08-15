<?php

namespace App\Policies;

use App\Models\BusinessEntity;
use App\Models\User;

class BusinessEntityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['super_admin', 'gm']);
    }

    public function view(User $user, BusinessEntity $businessEntity): bool
    {
        return $user->hasRole(['super_admin', 'gm']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    public function update(User $user, BusinessEntity $businessEntity): bool
    {
        return $user->hasRole('super_admin');
    }

    public function delete(User $user, BusinessEntity $businessEntity): bool
    {
        return $user->hasRole('super_admin');
    }

    public function restore(User $user, BusinessEntity $businessEntity): bool
    {
        return false;
    }

    public function forceDelete(User $user, BusinessEntity $businessEntity): bool
    {
        return false;
    }
}
