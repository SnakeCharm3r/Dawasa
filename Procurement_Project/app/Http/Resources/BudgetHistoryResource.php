<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BudgetHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'business_entity' => [
                'id' => $this->businessEntity?->id,
                'name' => $this->businessEntity?->name,
                'code' => $this->businessEntity?->code,
            ],
            'financial_year' => [
                'id' => $this->financialYear?->id,
                'name' => $this->financialYear?->name,
                'start_date' => $this->financialYear?->start_date?->toDateString(),
                'end_date' => $this->financialYear?->end_date?->toDateString(),
            ],
            'proposed_amount' => $this->proposed_amount,
            'approved_amount' => $this->approved_amount,
            'committed_amount' => $this->committed_amount,
            'spent_amount' => $this->spent_amount,
            'available_amount' => $this->available_amount,
            'status' => $this->status,
            'proposed_by' => [
                'id' => $this->proposedBy?->id,
                'name' => $this->proposedBy?->name,
                'email' => $this->proposedBy?->email,
            ],
            'approved_by' => $this->approvedBy ? [
                'id' => $this->approvedBy->id,
                'name' => $this->approvedBy->name,
                'email' => $this->approvedBy->email,
            ] : null,
            'submitted_at' => $this->submitted_at?->toDateTimeString(),
            'approved_at' => $this->approved_at?->toDateTimeString(),
            'approval_comments' => $this->approval_comments,
            'notes' => $this->notes,
            'transactions' => $this->transactions->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'transaction_type' => $transaction->transaction_type,
                    'amount' => $transaction->amount,
                    'reference_type' => $transaction->reference_type,
                    'reference_id' => $transaction->reference_id,
                    'description' => $transaction->description,
                    'transaction_date' => $transaction->transaction_date?->toDateTimeString(),
                    'created_by' => $transaction->createdBy ? [
                        'id' => $transaction->createdBy->id,
                        'name' => $transaction->createdBy->name,
                        'email' => $transaction->createdBy->email,
                    ] : null,
                ];
            }),
            'approvals' => $this->approvals->map(function ($approval) {
                return [
                    'id' => $approval->id,
                    'action' => $approval->action,
                    'comments' => $approval->comments,
                    'action_at' => $approval->action_at?->toDateTimeString(),
                    'actor' => $approval->actor ? [
                        'id' => $approval->actor->id,
                        'name' => $approval->actor->name,
                        'email' => $approval->actor->email,
                    ] : null,
                ];
            }),
            'activity_logs' => $this->activityLogs->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'action' => $activity->action,
                    'old_values' => $activity->old_values,
                    'new_values' => $activity->new_values,
                    'ip_address' => $activity->ip_address,
                    'created_at' => $activity->created_at?->toDateTimeString(),
                    'actor' => $activity->actor ? [
                        'id' => $activity->actor->id,
                        'name' => $activity->actor->name,
                        'email' => $activity->actor->email,
                    ] : null,
                ];
            }),
        ];
    }
}
