<?php

namespace App\Services;

use App\Models\BudgetTransaction;
use App\Models\EntityBudget;
use App\Models\FinancialYear;
use App\Models\ProcurementApproval;
use App\Models\PurchaseRequisition;
use App\Models\QuotationRecommendation;
use App\Models\SupplierQuotation;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class QuotationRecommendationService
{
    public function __construct(private readonly SupplierComplianceService $supplierCompliance) {}

    public function submitProformaForApproval(SupplierQuotation $quotation, array $data, User $actor): QuotationRecommendation
    {
        if ($quotation->status !== SupplierQuotation::STATUS_ACTIVE) {
            throw new \RuntimeException('Only an active proforma can be sent for approval.');
        }

        $requisition = $quotation->requisition;
        if (! in_array($requisition->status, [
            PurchaseRequisition::STATUS_APPROVED_FOR_SOURCING,
            PurchaseRequisition::STATUS_QUOTATIONS_READY,
            PurchaseRequisition::STATUS_RETURNED_TO_SOURCING,
        ], true)) {
            throw new \RuntimeException('The linked requisition is not ready for proforma approval.');
        }

        $this->assertNoActiveRecommendation($requisition);
        $this->ensureNonLowestReason($requisition, $quotation, $data);

        return DB::transaction(function () use ($quotation, $data, $actor, $requisition) {
            $recommendation = QuotationRecommendation::create([
                'purchase_requisition_id' => $requisition->id,
                'selected_quotation_id' => $quotation->id,
                'recommended_by' => $actor->id,
                'recommended_at' => now(),
                'reason_for_selection' => $data['reason_for_selection'],
                'non_lowest_price_reason' => $data['non_lowest_price_reason'] ?? null,
                'total_quoted_amount' => $quotation->total_amount,
                'status' => QuotationRecommendation::STATUS_DRAFT,
            ]);

            $requisition->update(['status' => PurchaseRequisition::STATUS_PENDING_REQUESTER_APPROVAL]);

            ProcurementApproval::create([
                'purchase_requisition_id' => $requisition->id,
                'quotation_recommendation_id' => $recommendation->id,
                'action' => ProcurementApproval::ACTION_RECOMMENDATION_SUBMITTED,
                'actor_id' => $actor->id,
                'comments' => $data['reason_for_selection'],
                'action_at' => now(),
            ]);

            return $recommendation->fresh(['selectedQuotation', 'requisition']);
        });
    }

    public function createDraft(PurchaseRequisition $requisition, array $data, User $user): QuotationRecommendation
    {
        $this->assertRequesterOwnsRequisition($requisition, $user);
        $this->assertEligibleRequisitionForRecommendation($requisition);
        $this->assertNoActiveRecommendation($requisition);

        $quotation = $this->findValidQuotationForRequisition($requisition, (int) $data['selected_quotation_id']);
        $this->ensureNonLowestReason($requisition, $quotation, $data);

        return QuotationRecommendation::create([
            'purchase_requisition_id' => $requisition->id,
            'selected_quotation_id' => $quotation->id,
            'recommended_by' => $user->id,
            'reason_for_selection' => $data['reason_for_selection'],
            'non_lowest_price_reason' => $data['non_lowest_price_reason'] ?? null,
            'total_quoted_amount' => $quotation->total_amount,
            'status' => QuotationRecommendation::STATUS_DRAFT,
        ]);
    }

    public function updateDraft(QuotationRecommendation $recommendation, array $data): QuotationRecommendation
    {
        if ($recommendation->status !== QuotationRecommendation::STATUS_DRAFT) {
            throw new \RuntimeException('Only draft recommendations can be updated.');
        }

        $quotation = $this->findValidQuotationForRequisition($recommendation->requisition, (int) $data['selected_quotation_id']);
        $this->ensureNonLowestReason($recommendation->requisition, $quotation, $data);

        $recommendation->update([
            'selected_quotation_id' => $quotation->id,
            'reason_for_selection' => $data['reason_for_selection'],
            'non_lowest_price_reason' => $data['non_lowest_price_reason'] ?? null,
            'total_quoted_amount' => $quotation->total_amount,
        ]);

        return $recommendation;
    }

    public function submit(QuotationRecommendation $recommendation): QuotationRecommendation
    {
        if ($recommendation->status !== QuotationRecommendation::STATUS_DRAFT) {
            throw new \RuntimeException('Only draft recommendations can be submitted.');
        }

        $requisition = $recommendation->requisition;
        $this->assertEligibleRequisitionForRecommendation($requisition);
        $quotation = $this->findValidQuotationForRequisition($requisition, $recommendation->selected_quotation_id);
        $this->ensureNonLowestReason($requisition, $quotation, [
            'selected_quotation_id' => $quotation->id,
            'reason_for_selection' => $recommendation->reason_for_selection,
            'non_lowest_price_reason' => $recommendation->non_lowest_price_reason,
        ]);

        DB::transaction(function () use ($recommendation) {
            $recommendation->update([
                'status' => QuotationRecommendation::STATUS_SUBMITTED,
                'recommended_at' => now(),
                'total_quoted_amount' => $recommendation->selectedQuotation->total_amount,
            ]);

            $requisition = $recommendation->requisition;
            $requisition->update(['status' => PurchaseRequisition::STATUS_PENDING_LINE_MANAGER_APPROVAL]);

            ProcurementApproval::create([
                'purchase_requisition_id' => $recommendation->purchase_requisition_id,
                'quotation_recommendation_id' => $recommendation->id,
                'action' => ProcurementApproval::ACTION_RECOMMENDATION_SUBMITTED,
                'actor_id' => auth()->id(),
                'comments' => null,
                'action_at' => now(),
            ]);
        });

        return $recommendation->fresh();
    }

    public function lineManagerApprove(QuotationRecommendation $recommendation, string $comments, User $actor): QuotationRecommendation
    {
        if ($recommendation->status !== QuotationRecommendation::STATUS_SUBMITTED) {
            throw new \RuntimeException('Only submitted recommendations can be approved by the line manager.');
        }

        return DB::transaction(function () use ($recommendation, $comments, $actor) {
            $requisition = $recommendation->requisition;
            $requisition->update(['status' => PurchaseRequisition::STATUS_PENDING_FINAL_APPROVAL]);

            ProcurementApproval::create([
                'purchase_requisition_id' => $requisition->id,
                'quotation_recommendation_id' => $recommendation->id,
                'action' => ProcurementApproval::ACTION_APPROVED,
                'actor_id' => $actor->id,
                'comments' => $comments,
                'action_at' => now(),
            ]);
        });

        return $recommendation->fresh();
    }

    public function requesterReturn(QuotationRecommendation $recommendation, string $comments, User $actor): QuotationRecommendation
    {
        if ($recommendation->status !== QuotationRecommendation::STATUS_DRAFT) {
            throw new \RuntimeException('Only draft recommendations can be returned by the requester.');
        }

        DB::transaction(function () use ($recommendation, $comments, $actor) {
            $recommendation->update(['status' => QuotationRecommendation::STATUS_DRAFT]);
            $recommendation->requisition->update(['status' => PurchaseRequisition::STATUS_QUOTATIONS_READY]);

            ProcurementApproval::create([
                'purchase_requisition_id' => $recommendation->purchase_requisition_id,
                'quotation_recommendation_id' => $recommendation->id,
                'action' => ProcurementApproval::ACTION_REQUESTER_RETURNED,
                'actor_id' => $actor->id,
                'comments' => $comments,
                'action_at' => now(),
            ]);
        });

        return $recommendation->fresh();
    }

    public function lineManagerReturn(QuotationRecommendation $recommendation, string $comments, User $actor): QuotationRecommendation
    {
        if ($recommendation->status !== QuotationRecommendation::STATUS_SUBMITTED) {
            throw new \RuntimeException('Only submitted recommendations can be returned by the line manager.');
        }

        DB::transaction(function () use ($recommendation, $comments, $actor) {
            $recommendation->update(['status' => QuotationRecommendation::STATUS_DRAFT]);
            $recommendation->requisition->update(['status' => PurchaseRequisition::STATUS_PENDING_REQUESTER_APPROVAL]);

            ProcurementApproval::create([
                'purchase_requisition_id' => $recommendation->purchase_requisition_id,
                'quotation_recommendation_id' => $recommendation->id,
                'action' => ProcurementApproval::ACTION_LINE_MANAGER_RETURNED,
                'actor_id' => $actor->id,
                'comments' => $comments,
                'action_at' => now(),
            ]);
        });

        return $recommendation->fresh();
    }

    public function approve(QuotationRecommendation $recommendation, string $comments, User $actor): QuotationRecommendation
    {
        if ($recommendation->status !== QuotationRecommendation::STATUS_SUBMITTED) {
            throw new \RuntimeException('Only submitted recommendations can be approved.');
        }

        $supplier = $recommendation->selectedQuotation?->supplier;
        if (! $supplier || ! $this->supplierCompliance->canParticipate($supplier)) {
            throw new \RuntimeException('The selected supplier must be approved, active, category-eligible, and fully compliant before award approval.');
        }

        return DB::transaction(function () use ($recommendation, $comments, $actor) {
            $requisition = $recommendation->requisition;
            $budget = $this->lockApprovedBudgetForRequisition($requisition);

            $originalCommitment = $requisition->committed_amount ?? 0;
            $finalAmount = $recommendation->total_quoted_amount;
            $difference = $finalAmount - $originalCommitment;

            if ($difference < 0) {
                $this->releaseDifference($budget, abs($difference), $requisition, $actor);
            } elseif ($difference > 0) {
                if ($budget->available_amount < $difference) {
                    throw new \RuntimeException(sprintf('Budget unavailable. Required: %s, available: %s', number_format($difference, 2, '.', ''), number_format($budget->available_amount, 2, '.', '')));
                }
                $this->commitDifference($budget, $difference, $requisition, $actor);
            }

            $recommendation->update(['status' => QuotationRecommendation::STATUS_APPROVED]);
            $requisition->update(['status' => PurchaseRequisition::STATUS_APPROVED_FOR_PURCHASE, 'committed_amount' => $finalAmount]);

            ProcurementApproval::create([
                'purchase_requisition_id' => $requisition->id,
                'quotation_recommendation_id' => $recommendation->id,
                'action' => ProcurementApproval::ACTION_APPROVED,
                'actor_id' => $actor->id,
                'comments' => $comments,
                'action_at' => now(),
            ]);

            return $recommendation->fresh();
        });
    }

    public function returnToSourcing(QuotationRecommendation $recommendation, string $comments, User $actor): QuotationRecommendation
    {
        if ($recommendation->status !== QuotationRecommendation::STATUS_SUBMITTED) {
            throw new \RuntimeException('Only submitted recommendations can be returned to sourcing.');
        }

        DB::transaction(function () use ($recommendation, $comments, $actor) {
            $recommendation->update(['status' => QuotationRecommendation::STATUS_RETURNED]);
            $recommendation->requisition->update(['status' => PurchaseRequisition::STATUS_RETURNED_TO_SOURCING]);

            ProcurementApproval::create([
                'purchase_requisition_id' => $recommendation->purchase_requisition_id,
                'quotation_recommendation_id' => $recommendation->id,
                'action' => ProcurementApproval::ACTION_RETURNED_TO_SOURCING,
                'actor_id' => $actor->id,
                'comments' => $comments,
                'action_at' => now(),
            ]);
        });

        return $recommendation->fresh();
    }

    public function reject(QuotationRecommendation $recommendation, string $comments, User $actor): QuotationRecommendation
    {
        if ($recommendation->status !== QuotationRecommendation::STATUS_SUBMITTED) {
            throw new \RuntimeException('Only submitted recommendations can be rejected.');
        }

        DB::transaction(function () use ($recommendation, $comments, $actor) {
            $recommendation->update(['status' => QuotationRecommendation::STATUS_REJECTED]);
            $recommendation->selectedQuotation->update([
                'status' => SupplierQuotation::STATUS_REJECTED,
                'rejected_by' => $actor->id,
                'rejected_at' => now(),
                'rejection_reason' => $comments,
            ]);
            $recommendation->requisition->update(['status' => PurchaseRequisition::STATUS_RETURNED_TO_SOURCING]);

            ProcurementApproval::create([
                'purchase_requisition_id' => $recommendation->purchase_requisition_id,
                'quotation_recommendation_id' => $recommendation->id,
                'action' => ProcurementApproval::ACTION_LINE_MANAGER_APPROVED,
                'actor_id' => $actor->id,
                'comments' => $comments,
                'action_at' => now(),
            ]);
        });

        return $recommendation->fresh();
    }

    private function assertRequesterOwnsRequisition(PurchaseRequisition $requisition, User $user): void
    {
        if ($user->id !== $requisition->requester_id) {
            throw new \RuntimeException('The requester can only create recommendations for their own requisitions.');
        }
    }

    private function assertEligibleRequisitionForRecommendation(PurchaseRequisition $requisition): void
    {
        if ($requisition->status !== PurchaseRequisition::STATUS_QUOTATIONS_READY) {
            throw new \RuntimeException('Recommendations can only be created for requisitions that have been marked ready for quotation recommendations.');
        }

        if ($this->validQuotationsForRequisition($requisition)->isEmpty()) {
            throw new \RuntimeException('The requisition has no valid supplier quotations available for recommendation.');
        }
    }

    private function assertNoActiveRecommendation(PurchaseRequisition $requisition): void
    {
        $active = $requisition->quotationRecommendations()->whereIn('status', [QuotationRecommendation::STATUS_DRAFT, QuotationRecommendation::STATUS_SUBMITTED])->exists();

        if ($active) {
            throw new \RuntimeException('An active recommendation already exists for this requisition.');
        }
    }

    private function findValidQuotationForRequisition(PurchaseRequisition $requisition, int $quotationId): SupplierQuotation
    {
        $quotation = SupplierQuotation::query()
            ->where('purchase_requisition_id', $requisition->id)
            ->valid()
            ->find($quotationId);

        if (! $quotation) {
            throw new \RuntimeException('The selected quotation is not valid for this requisition.');
        }

        return $quotation;
    }

    private function validQuotationsForRequisition(PurchaseRequisition $requisition)
    {
        return SupplierQuotation::query()
            ->where('purchase_requisition_id', $requisition->id)
            ->valid()
            ->orderBy('total_amount', 'asc')
            ->get();
    }

    private function lowestValidQuotationAmount(PurchaseRequisition $requisition): float
    {
        $quotation = $this->validQuotationsForRequisition($requisition)->first();

        return $quotation ? (float) $quotation->total_amount : 0.0;
    }

    private function ensureNonLowestReason(PurchaseRequisition $requisition, SupplierQuotation $quotation, array $data): void
    {
        $lowest = $this->lowestValidQuotationAmount($requisition);
        $selectedAmount = (float) $quotation->total_amount;

        if ($selectedAmount > $lowest && empty($data['non_lowest_price_reason'])) {
            throw new \RuntimeException('A reason is required when the selected quotation is not the lowest valid quotation.');
        }
    }

    private function lockApprovedBudgetForRequisition(PurchaseRequisition $requisition): EntityBudget
    {
        $financialYear = FinancialYear::query()->where('is_active', true)->first();

        if (! $financialYear) {
            throw new ModelNotFoundException('No active financial year exists.');
        }

        $budget = EntityBudget::query()
            ->where('business_entity_id', $requisition->business_entity_id)
            ->where('financial_year_id', $financialYear->id)
            ->where('status', EntityBudget::STATUS_APPROVED)
            ->lockForUpdate()
            ->first();

        if (! $budget) {
            throw new ModelNotFoundException('No approved budget found for the requisition business entity and active financial year.');
        }

        return $budget;
    }

    private function releaseDifference(EntityBudget $budget, float $amount, PurchaseRequisition $requisition, User $actor): void
    {
        BudgetTransaction::create([
            'entity_budget_id' => $budget->id,
            'transaction_type' => BudgetTransaction::TYPE_COMMITMENT_RELEASE,
            'amount' => $amount,
            'reference_type' => PurchaseRequisition::class,
            'reference_id' => $requisition->id,
            'description' => sprintf('Release difference for requisition %s after final quote approval.', $requisition->requisition_number),
            'created_by' => $actor->id,
            'transaction_date' => now(),
        ]);

        $budget->committed_amount = max(0, $budget->committed_amount - $amount);
        $budget->saveQuietly();
        $budget->syncAvailable();
    }

    private function commitDifference(EntityBudget $budget, float $amount, PurchaseRequisition $requisition, User $actor): void
    {
        BudgetTransaction::create([
            'entity_budget_id' => $budget->id,
            'transaction_type' => BudgetTransaction::TYPE_COMMITMENT,
            'amount' => $amount,
            'reference_type' => PurchaseRequisition::class,
            'reference_id' => $requisition->id,
            'description' => sprintf('Commit additional amount for requisition %s after final quote approval.', $requisition->requisition_number),
            'created_by' => $actor->id,
            'transaction_date' => now(),
        ]);

        $budget->committed_amount = $budget->committed_amount + $amount;
        $budget->saveQuietly();
        $budget->syncAvailable();
    }
}
