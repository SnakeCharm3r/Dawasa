<?php

namespace App\Policies;

use App\Models\PurchaseRequisition;
use App\Models\QuotationRecommendation;
use App\Models\User;
use App\Services\EntityAccessService;

class QuotationRecommendationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'accountant', 'gm', 'auditor', 'requester', 'line_manager', 'department_head', 'procurement_officer']);
    }

    public function view(User $user, QuotationRecommendation $recommendation): bool
    {
        if (! app(EntityAccessService::class)->canAccess($user, $recommendation->requisition->business_entity_id)) {
            return false;
        }

        if ($user->hasAnyRole(['super_admin', 'accountant', 'gm', 'auditor'])) {
            return true;
        }

        if ($user->hasRole('procurement_officer')) {
            return $recommendation->requisition->status !== PurchaseRequisition::STATUS_DRAFT;
        }

        if ($user->hasAnyRole(['line_manager', 'department_head'])) {
            return $user->id === $recommendation->requisition->line_manager_id
                || $user->id === $recommendation->requisition->requester_id;
        }

        return $user->id === $recommendation->requisition->requester_id;
    }

    public function create(User $user, PurchaseRequisition $requisition): bool
    {
        if (! $user->hasRole('requester')) {
            return false;
        }

        if ($user->id !== $requisition->requester_id) {
            return false;
        }

        return $requisition->status === PurchaseRequisition::STATUS_QUOTATIONS_READY;
    }

    public function update(User $user, QuotationRecommendation $recommendation): bool
    {
        if (! $user->hasRole('requester')) {
            return false;
        }

        return $user->id === $recommendation->recommended_by
            && $recommendation->status === QuotationRecommendation::STATUS_DRAFT
            && $recommendation->requisition->status === PurchaseRequisition::STATUS_QUOTATIONS_READY;
    }

    public function submit(User $user, QuotationRecommendation $recommendation): bool
    {
        if (! $user->hasRole('requester')) {
            return false;
        }

        if ($user->id !== $recommendation->requisition->requester_id) {
            return false;
        }

        return $recommendation->status === QuotationRecommendation::STATUS_DRAFT
            && $recommendation->requisition->status === PurchaseRequisition::STATUS_PENDING_REQUESTER_APPROVAL;
    }

    public function requesterSubmit(User $user, QuotationRecommendation $recommendation): bool
    {
        return $this->submit($user, $recommendation);
    }

    public function requesterReturn(User $user, QuotationRecommendation $recommendation): bool
    {
        return $this->submit($user, $recommendation);
    }

    public function lineManagerApprove(User $user, QuotationRecommendation $recommendation): bool
    {
        if (! $user->hasAnyRole(['line_manager', 'department_head'])) {
            return false;
        }

        if ($user->id !== $recommendation->requisition->line_manager_id) {
            return false;
        }

        return $recommendation->status === QuotationRecommendation::STATUS_SUBMITTED
            && $recommendation->requisition->status === PurchaseRequisition::STATUS_PENDING_LINE_MANAGER_APPROVAL
            && app(EntityAccessService::class)->canAccess($user, $recommendation->requisition->business_entity_id);
    }

    public function lineManagerReturn(User $user, QuotationRecommendation $recommendation): bool
    {
        return $this->lineManagerApprove($user, $recommendation);
    }
}
