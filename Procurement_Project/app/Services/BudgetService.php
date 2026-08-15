<?php

namespace App\Services;

use App\Models\BudgetApproval;
use App\Models\BudgetTransaction;
use App\Models\EntityBudget;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;

class BudgetService
{
    public function submitBudget(EntityBudget $budget, User $actor): EntityBudget
    {
        return DB::transaction(function () use ($budget, $actor) {
            $oldValues = ['status' => $budget->status];

            $budget->update([
                'status' => EntityBudget::STATUS_SUBMITTED,
                'submitted_at' => now(),
            ]);

            $this->recordApproval($budget, BudgetApproval::ACTION_SUBMITTED, $actor, null);
            ActivityLog::record($actor, 'entity_budget.submitted', $budget, $oldValues, ['status' => EntityBudget::STATUS_SUBMITTED]);

            return $budget;
        });
    }

    public function approveBudget(EntityBudget $budget, float $approvedAmount, ?string $comments, User $actor): EntityBudget
    {
        if ($actor->id === $budget->proposed_by) {
            throw new \RuntimeException('You cannot approve a budget that you proposed.');
        }

        return DB::transaction(function () use ($budget, $approvedAmount, $comments, $actor) {
            $oldValues = $budget->only(['status', 'approved_amount', 'approved_by', 'approved_at', 'approval_comments', 'available_amount']);

            $budget->fill([
                'approved_amount' => $approvedAmount,
                'approved_by' => $actor->id,
                'approved_at' => now(),
                'approval_comments' => $comments,
                'status' => EntityBudget::STATUS_APPROVED,
            ]);

            $budget->save();
            $budget->syncAvailable();

            $this->recordApproval($budget, BudgetApproval::ACTION_APPROVED, $actor, $comments);
            ActivityLog::record($actor, 'entity_budget.approved', $budget, $oldValues, $budget->only(['status', 'approved_amount', 'approved_by', 'approved_at', 'approval_comments', 'available_amount']));

            return $budget;
        });
    }

    public function returnBudget(EntityBudget $budget, string $comments, User $actor): EntityBudget
    {
        return DB::transaction(function () use ($budget, $comments, $actor) {
            $oldValues = $budget->only(['status', 'approval_comments']);

            $budget->update([
                'status' => EntityBudget::STATUS_RETURNED,
                'approval_comments' => $comments,
            ]);

            $this->recordApproval($budget, BudgetApproval::ACTION_RETURNED, $actor, $comments);
            ActivityLog::record($actor, 'entity_budget.returned', $budget, $oldValues, ['status' => EntityBudget::STATUS_RETURNED, 'approval_comments' => $comments]);

            return $budget;
        });
    }

    public function rejectBudget(EntityBudget $budget, string $comments, User $actor): EntityBudget
    {
        return DB::transaction(function () use ($budget, $comments, $actor) {
            $oldValues = $budget->only(['status', 'approval_comments']);

            $budget->update([
                'status' => EntityBudget::STATUS_REJECTED,
                'approval_comments' => $comments,
            ]);

            $this->recordApproval($budget, BudgetApproval::ACTION_REJECTED, $actor, $comments);
            ActivityLog::record($actor, 'entity_budget.rejected', $budget, $oldValues, ['status' => EntityBudget::STATUS_REJECTED, 'approval_comments' => $comments]);

            return $budget;
        });
    }

    public function postTransaction(EntityBudget $budget, array $data, ?User $actor): BudgetTransaction
    {
        if ($budget->status !== EntityBudget::STATUS_APPROVED) {
            throw new \RuntimeException('Transactions can only be posted to approved budgets.');
        }

        return DB::transaction(function () use ($budget, $data, $actor) {
            $transaction = BudgetTransaction::create([
                'entity_budget_id' => $budget->id,
                'transaction_type' => $data['transaction_type'],
                'amount' => $data['amount'],
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'description' => $data['description'] ?? null,
                'created_by' => $actor?->id,
                'transaction_date' => $data['transaction_date'] ?? now(),
            ]);

            ActivityLog::record($actor ?? $budget->proposedBy, 'entity_budget.transaction.created', $budget, [], $transaction->toArray());

            return $transaction;
        });
    }

    private function recordApproval(EntityBudget $budget, string $action, User $actor, ?string $comments): BudgetApproval
    {
        return BudgetApproval::create([
            'entity_budget_id' => $budget->id,
            'action' => $action,
            'actor_id' => $actor->id,
            'comments' => $comments,
            'action_at' => now(),
        ]);
    }
}
