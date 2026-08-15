<?php

namespace App\Policies;

use App\Models\PurchaseOrderApproval;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PurchaseOrderApprovalPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'auditor', 'accountant', 'procurement_officer', 'gm']);
    }

    public function view(User $user, PurchaseOrderApproval $approval): bool
    {
        return $user->hasAnyRole(['super_admin', 'auditor', 'accountant', 'procurement_officer', 'gm']);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, PurchaseOrderApproval $approval): bool
    {
        return false;
    }

    public function delete(User $user, PurchaseOrderApproval $approval): bool
    {
        return false;
    }
}
