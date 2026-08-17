<?php

namespace App\Http\Resources;

use App\Services\RequisitionBudgetService;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseRequisitionResource extends JsonResource
{
    public function toArray($request): array
    {
        $canSeeFinancials = $request->user()->hasAnyRole(['super_admin', 'accountant', 'gm', 'ceo', 'auditor'])
            || $request->user()->id === $this->requester_id
            || $request->user()->id === $this->line_manager_id;
        $canSeeFullBudget = $request->user()->hasAnyRole(['accountant', 'gm', 'ceo']);
        $isProcurementOnly = $request->user()->hasRole('procurement_officer')
            && ! $request->user()->hasAnyRole(['super_admin', 'accountant', 'gm', 'ceo', 'auditor']);

        $base = [
            'id' => $this->id,
            'requisition_number' => $this->requisition_number,
            'business_entity' => [
                'id' => $this->businessEntity?->id,
                'name' => $this->businessEntity?->name,
            ],
            'department' => [
                'id' => $this->department?->id,
                'name' => $this->department?->name,
            ],
            'supplier_category' => $this->supplierCategory ? [
                'id' => $this->supplierCategory->id,
                'code' => $this->supplierCategory->code,
                'name' => $this->supplierCategory->name,
            ] : null,
            'requester' => [
                'id' => $this->requester?->id,
                'name' => $this->requester?->name,
            ],
            'line_manager' => [
                'id' => $this->lineManager?->id,
                'name' => $this->lineManager?->name,
            ],
            'required_date' => $this->required_date?->toDateString(),
            'purpose' => $this->purpose,
            'status' => $this->status,
            'submitted_at' => $this->submitted_at?->toDateTimeString(),
            'approved_at' => $this->approved_at?->toDateTimeString(),
            'returned_at' => $this->returned_at?->toDateTimeString(),
            'rejected_at' => $this->rejected_at?->toDateTimeString(),
            'cancelled_at' => $this->cancelled_at?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'items' => $this->items->map(function ($item) use ($canSeeFinancials) {
                $data = [
                    'id' => $item->id,
                    'item_name' => $item->item_name,
                    'specification' => $item->specification,
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                    'notes' => $item->notes,
                ];

                if ($canSeeFinancials) {
                    $data['estimated_unit_price'] = $item->estimated_unit_price;
                    $data['estimated_total'] = $item->estimated_total;
                }

                return $data;
            }),
            'attachments' => $this->attachments->filter(fn ($attachment) => ! $isProcurementOnly || ! $attachment->is_confidential)->map(function ($attachment) {
                return [
                    'id' => $attachment->id,
                    'original_name' => $attachment->original_name,
                    'mime_type' => $attachment->mime_type,
                    'file_size' => $attachment->file_size,
                    'is_confidential' => $attachment->is_confidential,
                    'uploaded_by' => [
                        'id' => $attachment->uploader?->id,
                        'name' => $attachment->uploader?->name,
                    ],
                    'created_at' => $attachment->created_at?->toDateTimeString(),
                ];
            }),
            'approvals' => $this->approvals->map(function ($approval) use ($isProcurementOnly) {
                $data = [
                    'id' => $approval->id,
                    'action' => $approval->action,
                    'action_at' => $approval->action_at?->toDateTimeString(),
                    'actor' => [
                        'id' => $approval->actor?->id,
                        'name' => $approval->actor?->name,
                    ],
                ];
                if (! $isProcurementOnly) {
                    $data['comments'] = $approval->comments;
                }
                return $data;
            }),
            'activity_logs' => $this->when($canSeeFinancials && $this->relationLoaded('activityLogs'), fn () => $this->activityLogs->map(function ($log) use ($canSeeFullBudget) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'old_values' => $canSeeFullBudget ? $log->old_values : $this->withoutBudgetAmounts($log->old_values),
                    'new_values' => $canSeeFullBudget ? $log->new_values : $this->withoutBudgetAmounts($log->new_values),
                    'created_at' => $log->created_at?->toDateTimeString(),
                    'actor' => [
                        'id' => $log->actor?->id,
                        'name' => $log->actor?->name,
                    ],
                ];
            })),
        ];

        if ($canSeeFinancials) {
            $base['estimated_amount'] = $this->estimated_amount;
            $base['committed_amount'] = $this->committed_amount;
            $base['estimate_difference_reason'] = $this->estimate_difference_reason;
            $base['budget_shortfall_reason'] = $this->budget_shortfall_reason;
        }

        $budgetService = app(RequisitionBudgetService::class);
        $base['budget_check'] = $budgetService->visibleCheck($budgetService->checkAvailability($this->resource), $request->user());
        $base['budget_check_record'] = [
            'status' => $this->budget_check_status,
            'checked_at' => $this->budget_checked_at?->toDateTimeString(),
            'shortfall_acknowledged' => $this->budget_shortfall_acknowledged,
            'shortfall_acknowledged_at' => $this->budget_shortfall_acknowledged_at?->toDateTimeString(),
        ];

        return $base;
    }

    private function withoutBudgetAmounts(?array $values): array
    {
        return collect($values ?? [])->except([
            'budget_available_at_check',
            'budget_shortfall_amount',
            'available_amount',
            'approved_amount',
            'committed_amount',
            'spent_amount',
        ])->all();
    }
}
