<?php

namespace App\Policies;

use App\Models\EntityBudget;
use App\Models\User;

class EntityBudgetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['super_admin', 'accountant', 'gm', 'auditor']);
    }

    public function view(User $user, EntityBudget $entityBudget): bool
    {
        return $user->hasRole(['super_admin', 'accountant', 'gm', 'auditor']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['super_admin', 'accountant']);
    }

    public function update(User $user, EntityBudget $entityBudget): bool
    {
        return $user->hasRole(['super_admin', 'accountant']) && in_array($entityBudget->status, [
            EntityBudget::STATUS_DRAFT,
            EntityBudget::STATUS_RETURNED,
        ], true);
    }

    public function submit(User $user, EntityBudget $entityBudget): bool
    {
        return $user->hasRole(['super_admin', 'accountant']) && in_array($entityBudget->status, [
            EntityBudget::STATUS_DRAFT,
            EntityBudget::STATUS_RETURNED,
        ], true);
    }

    public function approve(User $user, EntityBudget $entityBudget): bool
    {
        return $user->hasRole(['super_admin', 'gm']) && $entityBudget->status === EntityBudget::STATUS_SUBMITTED;
    }

    public function return(User $user, EntityBudget $entityBudget): bool
    {
        return $this->approve($user, $entityBudget);
    }

    public function reject(User $user, EntityBudget $entityBudget): bool
    {
        return $this->approve($user, $entityBudget);
    }

    public function postTransaction(User $user, EntityBudget $entityBudget): bool
    {
        return $user->hasRole(['super_admin', 'accountant']) && $entityBudget->status === EntityBudget::STATUS_APPROVED;
    }

    public function delete(User $user, EntityBudget $entityBudget): bool
    {
        return false;
    }

    public function restore(User $user, EntityBudget $entityBudget): bool
    {
        return false;
    }

    public function forceDelete(User $user, EntityBudget $entityBudget): bool
    {
        return false;
    }
}
