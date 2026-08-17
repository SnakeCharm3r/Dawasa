<?php

namespace App\Policies;

use App\Models\SupplierInvoice;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Services\EntityAccessService;

class SupplierInvoicePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'auditor', 'accountant', 'gm', 'procurement_officer', 'requester', 'line_manager', 'department_head']);
    }

    public function view(User $user, SupplierInvoice $supplierInvoice): bool
    {
        if (! app(EntityAccessService::class)->canAccess($user, $supplierInvoice->business_entity_id)) {
            return false;
        }

        if ($user->hasAnyRole(['super_admin', 'auditor', 'accountant', 'gm'])) {
            return true;
        }

        if ($user->hasRole('procurement_officer')) {
            return true;
        }

        $requisition = $supplierInvoice->purchaseOrder?->requisition;

        if ($user->hasAnyRole(['line_manager', 'department_head'])) {
            return $requisition
                && ($requisition->line_manager_id === $user->id || $requisition->requester_id === $user->id);
        }

        if ($user->hasRole('requester')) {
            return $requisition && $requisition->requester_id === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'accountant']);
    }

    public function update(User $user, SupplierInvoice $supplierInvoice): bool
    {
        if (! $supplierInvoice->isEditable()) {
            return false;
        }

        return $user->hasAnyRole(['super_admin', 'accountant']);
    }

    public function delete(User $user, SupplierInvoice $supplierInvoice): bool
    {
        return false;
    }

    public function restore(User $user, SupplierInvoice $supplierInvoice): bool
    {
        return $user->hasRole('super_admin');
    }

    public function forceDelete(User $user, SupplierInvoice $supplierInvoice): bool
    {
        return false;
    }

    public function submit(User $user, SupplierInvoice $supplierInvoice): bool
    {
        if (! $supplierInvoice->isEditable()) {
            return false;
        }

        return $user->hasAnyRole(['super_admin', 'accountant']);
    }

    public function match(User $user, SupplierInvoice $supplierInvoice): bool
    {
        if (! in_array($supplierInvoice->status, [
            SupplierInvoice::STATUS_SUBMITTED,
            SupplierInvoice::STATUS_PENDING_MATCH,
        ], true)) {
            return false;
        }

        return $user->hasAnyRole(['super_admin', 'accountant']);
    }

    public function approveVariance(User $user, SupplierInvoice $supplierInvoice): bool
    {
        if ($supplierInvoice->status !== SupplierInvoice::STATUS_MATCHED_WITH_VARIANCE) {
            return false;
        }

        return $user->hasAnyRole(['super_admin', 'gm']);
    }

    public function cancel(User $user, SupplierInvoice $supplierInvoice): bool
    {
        if (! $supplierInvoice->canBeCancelled()) {
            return false;
        }

        return $user->hasAnyRole(['super_admin', 'accountant']);
    }
}
