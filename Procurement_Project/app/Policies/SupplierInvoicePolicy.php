<?php

namespace App\Policies;

use App\Models\SupplierInvoice;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SupplierInvoicePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'auditor', 'accountant', 'gm']);
    }

    public function view(User $user, SupplierInvoice $supplierInvoice): bool
    {
        if ($user->hasAnyRole(['super_admin', 'auditor', 'accountant', 'gm'])) {
            return true;
        }

        if ($user->hasRole('procurement_officer')) {
            return true;
        }

        if ($user->hasRole('requester') || $user->hasRole('department_head')) {
            return $supplierInvoice->purchaseOrder->requisition->department_id === $user->department_id;
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
