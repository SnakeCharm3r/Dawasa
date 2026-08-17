<?php

namespace App\Policies;

use App\Models\SupplierQuotation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Services\EntityAccessService;

class SupplierQuotationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'auditor', 'accountant', 'procurement_officer', 'gm', 'requester', 'line_manager', 'department_head']);
    }

    public function view(User $user, SupplierQuotation $supplierQuotation): bool
    {
        $requisition = $supplierQuotation->requisition;
        if (! $requisition || ! app(EntityAccessService::class)->canAccess($user, $requisition->business_entity_id)) {
            return false;
        }

        if ($user->hasAnyRole(['line_manager', 'department_head'])) {
            return $requisition->line_manager_id === $user->id || $requisition->requester_id === $user->id;
        }

        if ($user->hasRole('requester')) {
            return $requisition->requester_id === $user->id;
        }

        return $user->hasAnyRole(['super_admin', 'auditor', 'accountant', 'procurement_officer', 'gm']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'procurement_officer']);
    }

    public function update(User $user, SupplierQuotation $supplierQuotation): bool
    {
        return $user->hasAnyRole(['super_admin', 'procurement_officer']);
    }

    public function reject(User $user, SupplierQuotation $supplierQuotation): bool
    {
        return $supplierQuotation->status === SupplierQuotation::STATUS_ACTIVE
            && $user->hasAnyRole(['super_admin', 'procurement_officer']);
    }

    public function delete(User $user, SupplierQuotation $supplierQuotation): bool
    {
        return $user->hasAnyRole(['super_admin', 'procurement_officer']);
    }

    public function restore(User $user, SupplierQuotation $supplierQuotation): bool
    {
        return $user->hasRole('super_admin');
    }

    public function forceDelete(User $user, SupplierQuotation $supplierQuotation): bool
    {
        return $user->hasRole('super_admin');
    }
}
