<?php

namespace App\Policies;

use App\Models\ProcurementClosure;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class ProcurementClosurePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'procurement_officer', 'accountant', 'gm', 'auditor']);
    }

    public function view(User $user, ProcurementClosure $closure): bool
    {
        if ($user->hasAnyRole(['super_admin', 'procurement_officer', 'accountant', 'gm', 'auditor'])) {
            return true;
        }

        if ($closure->purchaseRequisition->requester_id === $user->id) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'procurement_officer']);
    }

    public function update(User $user, ProcurementClosure $closure): bool
    {
        if (! $user->hasAnyRole(['super_admin', 'procurement_officer'])) {
            return false;
        }

        return $closure->isEditable();
    }

    public function submit(User $user, ProcurementClosure $closure): bool
    {
        if (! $user->hasAnyRole(['super_admin', 'procurement_officer'])) {
            return false;
        }

        return $closure->canBeSubmitted();
    }

    public function confirm(User $user, ProcurementClosure $closure): bool
    {
        if (! $closure->isPendingRequesterConfirmation()) {
            return false;
        }

        if ($closure->purchaseRequisition->requester_id === $user->id) {
            return true;
        }

        return $user->hasRole('super_admin');
    }

    public function return(User $user, ProcurementClosure $closure): bool
    {
        if (! $closure->isPendingRequesterConfirmation()) {
            return false;
        }

        if ($closure->purchaseRequisition->requester_id === $user->id) {
            return true;
        }

        return $user->hasRole('super_admin');
    }

    public function close(User $user, ProcurementClosure $closure): bool
    {
        if (! $user->hasAnyRole(['super_admin', 'procurement_officer'])) {
            return false;
        }

        if (! $closure->canBeClosed()) {
            return false;
        }

        if ($closure->requester_confirmed_by === $user->id && ! $user->hasRole('super_admin')) {
            return false;
        }

        return true;
    }

    public function closeWithException(User $user, ProcurementClosure $closure): bool
    {
        return $user->hasAnyRole(['super_admin', 'gm']);
    }

    public function cancel(User $user, ProcurementClosure $closure): bool
    {
        if (! $user->hasAnyRole(['super_admin', 'procurement_officer'])) {
            return false;
        }

        return $closure->canBeCancelled();
    }

    public function delete(User $user, ProcurementClosure $procurementClosure): bool
    {
        return false;
    }

    public function restore(User $user, ProcurementClosure $procurementClosure): bool
    {
        return false;
    }

    public function forceDelete(User $user, ProcurementClosure $procurementClosure): bool
    {
        return false;
    }
}
