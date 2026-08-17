<?php

namespace App\Policies;

use App\Models\PurchaseRequisition;
use App\Models\User;

class PurchaseRequisitionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'accountant', 'gm', 'ceo', 'auditor', 'procurement_officer', 'department_head', 'line_manager', 'requester']);
    }

    public function view(User $user, PurchaseRequisition $purchaseRequisition): bool
    {
        if ($user->hasRole('gm')
            && ! app(\App\Services\EntityAccessService::class)->canAccess($user, $purchaseRequisition->business_entity_id)) {
            return false;
        }

        // Ownership always grants read access, even when the owner also has a
        // managerial role or has since moved to another department.
        if ($user->id === $purchaseRequisition->requester_id) {
            return true;
        }

        if (! app(\App\Services\EntityAccessService::class)->canAccess($user, $purchaseRequisition->business_entity_id)) {
            return false;
        }

        if ($user->hasAnyRole(['super_admin', 'accountant', 'gm', 'ceo', 'auditor'])) {
            return true;
        }

        if ($user->hasRole('procurement_officer')) {
            return in_array($purchaseRequisition->status, [
                PurchaseRequisition::STATUS_APPROVED_FOR_SOURCING,
                PurchaseRequisition::STATUS_QUOTATIONS_READY,
                PurchaseRequisition::STATUS_RETURNED_TO_SOURCING,
            ], true);
        }

        if ($user->hasAnyRole(['line_manager', 'department_head'])) {
            return $user->id === $purchaseRequisition->line_manager_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->department_id !== null
            && ($user->hasAnyRole(['line_manager', 'department_head']) || $user->hasRole('requester'))
            && $user->assignedLineManagerInDepartment() !== null;
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
            && in_array($purchaseRequisition->status, [PurchaseRequisition::STATUS_DRAFT, PurchaseRequisition::STATUS_RETURNED, PurchaseRequisition::STATUS_SUBMITTED, PurchaseRequisition::STATUS_PENDING_GM_APPROVAL], true);
    }

    public function approve(User $user, PurchaseRequisition $purchaseRequisition): bool
    {
        if ($user->id === $purchaseRequisition->requester_id) {
            return false;
        }

        if ($purchaseRequisition->status === PurchaseRequisition::STATUS_SUBMITTED) {
            $requester = $purchaseRequisition->requester;

            return $user->id === $purchaseRequisition->line_manager_id
                && $user->department_id === $purchaseRequisition->department_id
                && $requester !== null
                && $user->canManageDepartmentUser($requester);
        }

        return $purchaseRequisition->status === PurchaseRequisition::STATUS_PENDING_GM_APPROVAL
            && $user->hasRole('gm')
            && app(\App\Services\EntityAccessService::class)->canAccess($user, $purchaseRequisition->business_entity_id);
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
