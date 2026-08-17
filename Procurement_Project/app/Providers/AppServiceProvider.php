<?php

namespace App\Providers;

use App\Models\BusinessEntity;
use App\Models\Department;
use App\Models\EntityBudget;
use App\Models\FinancialYear;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderApproval;
use App\Models\PurchaseRequisition;
use App\Models\QuotationRecommendation;
use App\Models\User;
use App\Policies\BusinessEntityPolicy;
use App\Policies\DepartmentPolicy;
use App\Policies\EntityBudgetPolicy;
use App\Policies\FinancialYearPolicy;
use App\Policies\PurchaseOrderPolicy;
use App\Policies\PurchaseOrderApprovalPolicy;
use App\Policies\PurchaseRequisitionPolicy;
use App\Policies\QuotationRecommendationPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // The CEO is the organisation-wide oversight role. Keep this override in
        // one place so new policies automatically inherit CEO access.
        Gate::before(function (User $user): ?bool {
            return $user->hasRole('ceo') ? true : null;
        });

        Gate::policy(BusinessEntity::class, BusinessEntityPolicy::class);
        Gate::policy(Department::class, DepartmentPolicy::class);
        Gate::policy(EntityBudget::class, EntityBudgetPolicy::class);
        Gate::policy(FinancialYear::class, FinancialYearPolicy::class);
        Gate::policy(PurchaseRequisition::class, PurchaseRequisitionPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(QuotationRecommendation::class, QuotationRecommendationPolicy::class);
        Gate::policy(PurchaseOrder::class, PurchaseOrderPolicy::class);
        Gate::policy(PurchaseOrderApproval::class, PurchaseOrderApprovalPolicy::class);
    }
}
