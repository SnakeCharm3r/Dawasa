<?php

namespace App\Services;

use App\Models\EntityBudget;
use App\Models\FinancialYear;
use App\Models\PurchaseRequisition;
use App\Models\BudgetTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class RequisitionBudgetService
{
    public function verifyBudgetForSubmission(PurchaseRequisition $requisition): EntityBudget
    {
        $financialYear = FinancialYear::query()
            ->where('is_active', true)
            ->first();

        if (! $financialYear) {
            throw new \RuntimeException('No active financial year exists.');
        }

        $budget = EntityBudget::query()
            ->where('business_entity_id', $requisition->business_entity_id)
            ->where('financial_year_id', $financialYear->id)
            ->where('status', EntityBudget::STATUS_APPROVED)
            ->first();

        if (! $budget) {
            throw new ModelNotFoundException('No approved budget found for the requested entity and active financial year.');
        }

        if ($budget->available_amount < $requisition->estimated_amount) {
            throw new \RuntimeException(sprintf(
                'Budget insufficient. Available: %s, requested: %s',
                number_format($budget->available_amount, 2, '.', ''),
                number_format($requisition->estimated_amount, 2, '.', '')
            ));
        }

        return $budget;
    }

    public function commitRequisition(PurchaseRequisition $requisition, EntityBudget $budget, User $actor): BudgetTransaction
    {
        return DB::transaction(function () use ($requisition, $budget, $actor) {
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
