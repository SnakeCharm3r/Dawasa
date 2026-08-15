<?php

namespace App\Policies;

use App\Models\PurchaseRequisition;
use App\Models\User;

class PurchaseRequisitionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'accountant', 'gm', 'auditor', 'procurement_officer', 'department_head', 'line_manager', 'requester']);
    }

    public function view(User $user, PurchaseRequisition $purchaseRequisition): bool
    {
        if ($user->hasAnyRole(['super_admin', 'accountant', 'gm', 'auditor'])) {
            return true;
        }

        if ($user->hasRole('procurement_officer')) {
            return in_array($purchaseRequisition->status, [
                PurchaseRequisition::STATUS_APPROVED_FOR_SOURCING,
                PurchaseRequisition::STATUS_QUOTATIONS_READY,
                PurchaseRequisition::STATUS_RETURNED_TO_SOURCING,
            ], true);
        }

        if ($user->hasRole('department_head') || $user->hasRole('line_manager')) {
            return $user->department_id === $purchaseRequisition->department_id;
        }

        return $user->id === $purchaseRequisition->requester_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('requester') && $user->department_id !== null && $user->line_manager_id !== null;
    }

    public function update(User $user, PurchaseRequisition $purchaseRequisition): bool
    {
        return $user->id === $purchaseRequisition->requester_id
            && in_array($purchaseRequisition->status, [PurchaseRequisition::STATUS_DRAFT, PurchaseRequisition::STATUS_RETURNED], true);
    }

    public function submit(User $user, PurchaseRequisition $purchaseRequisition): bool
    {
        return $user->id === $purchaseRequisition->requester_id
            && in_array($purchaseRequisition->status, [PurchaseRequisition::STATUS_DRAFT, PurchaseRequisition::STATUS_RETURNED], true);
    }

    public function markQuotationsReady(User $user, PurchaseRequisition $purchaseRequisition): bool
    {
        if (! $user->hasRole(['procurement_officer', 'super_admin'])) {
            return false;
        }

        return in_array($purchaseRequisition->status, [
            PurchaseRequisition::STATUS_APPROVED_FOR_SOURCING,
            PurchaseRequisition::STATUS_RETURNED_TO_SOURCING,
        ], true);
    }

    public function cancel(User $user, PurchaseRequisition $purchaseRequisition): bool
    {
        return $user->id === $purchaseRequisition->requester_id
            && in_array($purchaseRequisition->status, [PurchaseRequisition::STATUS_DRAFT, PurchaseRequisition::STATUS_RETURNED, PurchaseRequisition::STATUS_SUBMITTED], true);
    }

    public function approve(User $user, PurchaseRequisition $purchaseRequisition): bool
    {
        if (! in_array($purchaseRequisition->status, [PurchaseRequisition::STATUS_SUBMITTED], true)) {
            return false;
        }

        if ($user->id === $purchaseRequisition->requester_id) {
            return false;
        }

        if ($user->hasRole('line_manager')) {
            return $user->department_id === $purchaseRequisition->department_id;
        }

        return $user->hasRole('department_head');
    }

    public function return(User $user, PurchaseRequisition $purchaseRequisition): bool
    {
        return $this->approve($user, $purchaseRequisition);
    }

    public function reject(User $user, PurchaseRequisition $purchaseRequisition): bool
    {
        return $this->approve($user, $purchaseRequisition);
    }

    public function uploadAttachment(User $user, PurchaseRequisition $purchaseRequisition): bool
    {
        return $user->id === $purchaseRequisition->requester_id
            && in_array($purchaseRequisition->status, [PurchaseRequisition::STATUS_DRAFT, PurchaseRequisition::STATUS_RETURNED], true);
    }

    public function deleteAttachment(User $user, PurchaseRequisition $purchaseRequisition): bool
    {
        return $this->uploadAttachment($user, $purchaseRequisition);
    }

    public function viewAttachment(User $user, PurchaseRequisition $purchaseRequisition): bool
    {
        return $this->view($user, $purchaseRequisition);
    }
}
