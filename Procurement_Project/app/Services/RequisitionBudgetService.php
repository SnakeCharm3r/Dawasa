<?php

namespace App\Services;

use App\Models\EntityBudget;
use App\Models\FinancialYear;
use App\Models\PurchaseRequisition;
use App\Models\BudgetTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RequisitionBudgetService
{
    public function checkAvailability(PurchaseRequisition $requisition): array
    {
        $financialYear = FinancialYear::query()
            ->where('is_active', true)
            ->first();

        if (! $financialYear) {
            return $this->unavailableSnapshot($requisition, 'no_active_financial_year', 'No active financial year is configured.');
        }

        $budget = EntityBudget::query()
            ->where('business_entity_id', $requisition->business_entity_id)
            ->where('financial_year_id', $financialYear->id)
            ->where('status', EntityBudget::STATUS_APPROVED)
            ->first();

        if (! $budget) {
            return $this->unavailableSnapshot($requisition, 'no_approved_budget', 'No approved budget exists for this organisation in the active financial year.', $financialYear->id, $financialYear->name);
        }

        $approved = (float) ($budget->approved_amount ?? 0);
        $committed = (float) $budget->committed_amount;
        $spent = (float) $budget->spent_amount;
        $currentAvailable = $approved - $committed - $spent;
        $existingCommitment = 0.0;
        if ($requisition->exists) {
            $committedForRequest = (float) BudgetTransaction::query()
                ->where('entity_budget_id', $budget->id)
                ->where('reference_type', PurchaseRequisition::class)
                ->where('reference_id', $requisition->id)
                ->where('transaction_type', BudgetTransaction::TYPE_COMMITMENT)
                ->sum('amount');
            $releasedForRequest = (float) BudgetTransaction::query()
                ->where('entity_budget_id', $budget->id)
                ->where('reference_type', PurchaseRequisition::class)
                ->where('reference_id', $requisition->id)
                ->where('transaction_type', BudgetTransaction::TYPE_COMMITMENT_RELEASE)
                ->sum('amount');
            $existingCommitment = max(0, $committedForRequest - $releasedForRequest);
        }
        $available = $currentAvailable + $existingCommitment;
        $requested = (float) $requisition->estimated_amount;
        $shortfall = max(0, $requested - $available);

        return [
            'status' => $shortfall > 0 ? 'shortfall' : 'sufficient',
            'sufficient' => $shortfall <= 0,
            'requires_acknowledgement' => $shortfall > 0,
            'message' => $shortfall > 0
                ? 'The request exceeds the currently available budget. It may proceed with a funding or loan justification.'
                : 'The current approved budget can accommodate this request.',
            'entity_budget_id' => $budget->id,
            'financial_year_id' => $financialYear->id,
            'financial_year' => $financialYear->name,
            'approved_amount' => $this->money($approved),
            'committed_amount' => $this->money($committed),
            'spent_amount' => $this->money($spent),
            'available_amount' => $this->money($available),
            'current_ledger_available_amount' => $this->money($currentAvailable),
            'requested_amount' => $this->money($requested),
            'projected_available_amount' => $this->money($available - $requested),
            'shortfall_amount' => $this->money($shortfall),
            'checked_at' => now()->toDateTimeString(),
        ];
    }

    public function approvedBudget(PurchaseRequisition $requisition): ?EntityBudget
    {
        $financialYear = FinancialYear::query()->where('is_active', true)->first();

        if (! $financialYear) {
            return null;
        }

        return EntityBudget::query()
            ->where('business_entity_id', $requisition->business_entity_id)
            ->where('financial_year_id', $financialYear->id)
            ->where('status', EntityBudget::STATUS_APPROVED)
            ->first();
    }

    public function recordCheck(PurchaseRequisition $requisition, array $check, ?User $acknowledgedBy = null, ?string $reason = null): PurchaseRequisition
    {
        $acknowledged = $check['requires_acknowledgement'] && $acknowledgedBy !== null;

        $requisition->update([
            'budget_check_status' => $check['status'],
            'budget_available_at_check' => $check['available_amount'],
            'budget_shortfall_amount' => $check['shortfall_amount'],
            'budget_checked_at' => now(),
            'budget_shortfall_acknowledged' => $acknowledged,
            'budget_shortfall_acknowledged_at' => $acknowledged ? now() : null,
            'budget_shortfall_acknowledged_by' => $acknowledgedBy?->id,
            'budget_shortfall_reason' => $acknowledged ? $reason : null,
        ]);

        return $requisition;
    }

    public function visibleCheck(array $check, User $user): array
    {
        $visible = [
            'status' => $check['status'],
            'sufficient' => $check['sufficient'],
            'requires_acknowledgement' => $check['requires_acknowledgement'],
            'message' => $check['sufficient']
                ? 'The organisation budget check is complete and available for this requisition.'
                : 'The organisation budget check requires a funding review. The requisition may proceed with a funding or loan justification.',
            'checked_at' => $check['checked_at'],
        ];

        if (! $user->hasAnyRole(['accountant', 'gm', 'ceo'])) {
            return $visible;
        }

        $used = $check['committed_amount'] === null || $check['spent_amount'] === null
            ? null
            : (float) $check['committed_amount'] + (float) $check['spent_amount'];

        return [
            ...$visible,
            'total_allocated_budget' => $check['approved_amount'],
            'total_used_amount' => $used === null ? null : $this->money($used),
            'available_amount' => $check['current_ledger_available_amount'] ?? $check['available_amount'],
        ];
    }

    public function commitRequisition(PurchaseRequisition $requisition, EntityBudget $budget, User $actor): BudgetTransaction
    {
        return DB::transaction(function () use ($requisition, $budget, $actor) {
            $budget = EntityBudget::query()->lockForUpdate()->findOrFail($budget->id);
            $transaction = BudgetTransaction::create([
                'entity_budget_id' => $budget->id,
                'transaction_type' => BudgetTransaction::TYPE_COMMITMENT,
                'amount' => $requisition->estimated_amount,
                'reference_type' => PurchaseRequisition::class,
                'reference_id' => $requisition->id,
                'description' => sprintf('Commitment for requisition %s', $requisition->requisition_number),
                'created_by' => $actor->id,
                'transaction_date' => now(),
            ]);

            $budget->committed_amount = $budget->committed_amount + $transaction->amount;
            $budget->syncAvailable();

            return $transaction;
        });
    }

    private function unavailableSnapshot(PurchaseRequisition $requisition, string $status, string $message, ?int $financialYearId = null, ?string $financialYear = null): array
    {
        $requested = (float) $requisition->estimated_amount;

        return [
            'status' => $status,
            'sufficient' => false,
            'requires_acknowledgement' => true,
            'message' => $message.' The request may still proceed with a funding or loan justification.',
            'entity_budget_id' => null,
            'financial_year_id' => $financialYearId,
            'financial_year' => $financialYear,
            'approved_amount' => null,
            'committed_amount' => null,
            'spent_amount' => null,
            'available_amount' => null,
            'requested_amount' => $this->money($requested),
            'projected_available_amount' => null,
            'shortfall_amount' => $this->money($requested),
            'checked_at' => now()->toDateTimeString(),
        ];
    }

    private function money(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }

    public function releaseCommitment(PurchaseRequisition $requisition, EntityBudget $budget, User $actor): BudgetTransaction
    {
        return DB::transaction(function () use ($requisition, $budget, $actor) {
            $transaction = BudgetTransaction::create([
                'entity_budget_id' => $budget->id,
                'transaction_type' => BudgetTransaction::TYPE_COMMITMENT_RELEASE,
                'amount' => $requisition->estimated_amount,
                'reference_type' => PurchaseRequisition::class,
                'reference_id' => $requisition->id,
                'description' => sprintf('Commitment release for cancelled requisition %s', $requisition->requisition_number),
                'created_by' => $actor->id,
                'transaction_date' => now(),
            ]);

            $budget->committed_amount = max(0, $budget->committed_amount - $transaction->amount);
            $budget->syncAvailable();

            return $transaction;
        });
    }
}
