<?php

namespace App\Policies;

use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PurchaseOrderPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            'super_admin', 'auditor', 'accountant', 'procurement_officer', 'gm', 'requester', 'department_head',
        ]);
    }

    public function view(User $user, PurchaseOrder $purchaseOrder): bool
    {
        if ($user->hasAnyRole(['super_admin', 'auditor', 'accountant', 'procurement_officer'])) {
            return true;
        }

        if ($user->hasRole('gm')) {
            return in_array($purchaseOrder->status, [
                PurchaseOrder::STATUS_CONFIRMED,
                PurchaseOrder::STATUS_ISSUED,
                PurchaseOrder::STATUS_ACKNOWLEDGED,
                PurchaseOrder::STATUS_PARTIALLY_RECEIVED,
                PurchaseOrder::STATUS_FULLY_RECEIVED,
                PurchaseOrder::STATUS_CLOSED,
            ], true);
        }

        $requisition = $purchaseOrder->requisition ?? $purchaseOrder->requisition()->first();

        if ($user->hasRole('requester')) {
            return $purchaseOrder->status === PurchaseOrder::STATUS_ISSUED
                && $requisition && $requisition->requester_id === $user->id;
        }

        if ($user->hasRole('department_head')) {
            return $purchaseOrder->status === PurchaseOrder::STATUS_ISSUED
                && $requisition && $requisition->department_id === $user->department_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('procurement_officer');
    }

    public function update(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->hasRole('procurement_officer') && $purchaseOrder->status === PurchaseOrder::STATUS_DRAFT;
    }

    public function submitForConfirmation(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->hasRole('procurement_officer') && $purchaseOrder->status === PurchaseOrder::STATUS_DRAFT;
    }

    public function issue(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->hasRole('procurement_officer') && $purchaseOrder->status === PurchaseOrder::STATUS_CONFIRMED;
    }

    public function confirm(User $user, PurchaseOrder $purchaseOrder): bool
    {
        if (! $user->hasRole('accountant')) {
            return false;
        }

        if ($purchaseOrder->status !== PurchaseOrder::STATUS_PENDING_ACCOUNTANT_CONFIRMATION) {
            return false;
        }

        return $user->id !== $purchaseOrder->preparerId();
    }

    public function returnToProcurement(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->hasRole('accountant')
            && $purchaseOrder->status === PurchaseOrder::STATUS_PENDING_ACCOUNTANT_CONFIRMATION;
    }

    public function reject(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->hasRole('accountant')
            && $purchaseOrder->status === PurchaseOrder::STATUS_PENDING_ACCOUNTANT_CONFIRMATION;
    }

    public function cancel(User $user, PurchaseOrder $purchaseOrder): bool
    {
        if (! $purchaseOrder->canBeCancelled()) {
            return false;
        }

        return $user->hasAnyRole(['procurement_officer', 'super_admin']);
    }

    public function acknowledge(User $user, PurchaseOrder $purchaseOrder): bool
    {
        if ($purchaseOrder->status !== PurchaseOrder::STATUS_ISSUED) {
            return false;
        }

        return $user->hasAnyRole(['procurement_officer', 'super_admin', 'accountant']);
    }
}
