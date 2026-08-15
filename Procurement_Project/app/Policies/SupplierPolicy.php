<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SupplierPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'auditor', 'accountant', 'procurement_officer', 'gm']);
    }

    public function view(User $user, Supplier $supplier): bool
    {
        return $user->hasAnyRole(['super_admin', 'auditor', 'accountant', 'procurement_officer', 'gm']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'procurement_officer']);
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $user->hasAnyRole(['super_admin', 'procurement_officer']);
    }

    public function delete(User $user, Supplier $supplier): bool
    {
        return $user->hasAnyRole(['super_admin', 'procurement_officer']);
    }

    public function restore(User $user, Supplier $supplier): bool
    {
        return $user->hasRole('super_admin');
    }

    public function forceDelete(User $user, Supplier $supplier): bool
    {
        return $user->hasRole('super_admin');
    }
}
