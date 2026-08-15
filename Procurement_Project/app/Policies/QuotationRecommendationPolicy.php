<?php

namespace App\Policies;

use App\Models\PurchaseRequisition;
use App\Models\QuotationRecommendation;
use App\Models\User;

class QuotationRecommendationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'accountant', 'gm', 'auditor', 'requester', 'department_head', 'procurement_officer']);
    }

    public function view(User $user, QuotationRecommendation $recommendation): bool
    {
        if ($user->hasAnyRole(['super_admin', 'accountant', 'gm', 'auditor'])) {
            return true;
        }

        if ($user->hasRole('procurement_officer')) {
            return $recommendation->requisition->status !== PurchaseRequisition::STATUS_DRAFT;
        }

        if ($user->hasRole('department_head')) {
            return $user->department_id === $recommendation->requisition->department_id;
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
        return $this->update($user, $recommendation);
    }

    public function approve(User $user, QuotationRecommendation $recommendation): bool
    {
        if (! $user->hasRole('gm')) {
            return false;
        }

        if ($user->id === $recommendation->requisition->requester_id) {
            return false;
        }

        return $recommendation->status === QuotationRecommendation::STATUS_SUBMITTED;
    }

    public function returnToSourcing(User $user, QuotationRecommendation $recommendation): bool
    {
        return $this->approve($user, $recommendation);
    }

    public function reject(User $user, QuotationRecommendation $recommendation): bool
    {
        return $this->approve($user, $recommendation);
    }
}
