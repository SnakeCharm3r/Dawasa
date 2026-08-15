<?php

namespace App\Policies;

use App\Models\QuotationRecommendation;
use App\Models\User;

class ProcurementApprovalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'accountant', 'gm', 'auditor', 'department_head', 'procurement_officer']);
    }

    public function view(User $user, QuotationRecommendation $recommendation): bool
    {
        return app(QuotationRecommendationPolicy::class)->view($user, $recommendation);
    }

    public function approve(User $user, QuotationRecommendation $recommendation): bool
    {
        return $user->hasRole('gm') && $recommendation->status === QuotationRecommendation::STATUS_SUBMITTED;
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
