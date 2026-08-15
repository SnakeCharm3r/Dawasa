<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseRequisitionResource extends JsonResource
{
    public function toArray($request): array
    {
        $canSeeFinancials = $request->user()->hasAnyRole(['super_admin', 'accountant', 'gm', 'auditor'])
            || $request->user()->id === $this->requester_id
            || $request->user()->id === $this->line_manager_id;

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
            'attachments' => $this->attachments->map(function ($attachment) {
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
            'approvals' => $this->approvals->map(function ($approval) {
                return [
                    'id' => $approval->id,
                    'action' => $approval->action,
                    'comments' => $approval->comments,
                    'action_at' => $approval->action_at?->toDateTimeString(),
                    'actor' => [
                        'id' => $approval->actor?->id,
                        'name' => $approval->actor?->name,
                    ],
                ];
            }),
        ];

        if ($canSeeFinancials) {
            $base['estimated_amount'] = $this->estimated_amount;
            $base['committed_amount'] = $this->committed_amount;
            $base['estimate_difference_reason'] = $this->estimate_difference_reason;
        }

        return $base;
    }
}
