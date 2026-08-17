<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class QuotationRecommendationResource extends JsonResource
{
    public function toArray($request): array
    {
        $data = [
            'id' => $this->id,
            'purchase_requisition_id' => $this->purchase_requisition_id,
            'selected_quotation' => [
                'id' => $this->selectedQuotation?->id,
                'quotation_number' => $this->selectedQuotation?->quotation_number,
                'supplier' => [
                    'id' => $this->selectedQuotation?->supplier?->id,
                    'name' => $this->selectedQuotation?->supplier?->name,
                    'compliance_status' => $this->selectedQuotation?->supplier?->compliance_status,
                    'award_eligibility' => $this->selectedQuotation?->supplier?->award_eligibility,
                    'performance_grade' => $this->selectedQuotation?->supplier?->currentPerformance?->grade,
                ],
                'total_amount' => $this->selectedQuotation?->total_amount,
                'valid_until' => $this->selectedQuotation?->valid_until?->toDateString(),
                'status' => $this->selectedQuotation?->status,
            ],
            'recommended_by' => [
                'id' => $this->recommendedBy?->id,
                'name' => $this->recommendedBy?->name,
            ],
            'recommended_at' => $this->recommended_at?->toDateTimeString(),
            'reason_for_selection' => $this->reason_for_selection,
            'non_lowest_price_reason' => $this->non_lowest_price_reason,
            'total_quoted_amount' => $this->total_quoted_amount,
            'status' => $this->status,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'procurement_approvals' => $this->procurementApprovals->map(function ($approval) {
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

        if ($request->user()->hasAnyRole(['super_admin', 'accountant', 'gm', 'ceo', 'auditor'])) {
            $data['requisition'] = [
                'id' => $this->requisition?->id,
                'requisition_number' => $this->requisition?->requisition_number,
                'status' => $this->requisition?->status,
            ];
        }

        return $data;
    }
}
