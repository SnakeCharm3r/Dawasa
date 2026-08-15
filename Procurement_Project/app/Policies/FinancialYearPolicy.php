<?php

namespace App\Policies;

use App\Models\FinancialYear;
use App\Models\User;

class FinancialYearPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['super_admin', 'gm', 'auditor']);
    }

    public function view(User $user, FinancialYear $financialYear): bool
    {
        return $user->hasRole(['super_admin', 'gm', 'auditor']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    public function update(User $user, FinancialYear $financialYear): bool
    {
        return $user->hasRole('super_admin');
    }

    public function delete(User $user, FinancialYear $financialYear): bool
    {
        return $user->hasRole('super_admin');
    }

    public function restore(User $user, FinancialYear $financialYear): bool
    {
        return false;
    }

    public function forceDelete(User $user, FinancialYear $financialYear): bool
    {
        return false;
    }
}
