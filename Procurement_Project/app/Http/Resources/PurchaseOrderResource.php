<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
{
    public function toArray($request): array
    {
        $canSeeFinancials = $request->user()->hasAnyRole(['super_admin', 'accountant', 'gm', 'auditor', 'procurement_officer'])
            || ($this->requisition && $request->user()->id === $this->requisition->requester_id)
            || ($this->requisition && $request->user()->id === $this->requisition->line_manager_id);

        $canSeeBudgetData = $request->user()->hasAnyRole(['super_admin', 'accountant', 'gm', 'auditor'])
            || ($this->requisition && $request->user()->id === $this->requisition->requester_id)
            || ($this->requisition && $request->user()->id === $this->requisition->line_manager_id);

        $data = [
            'id' => $this->id,
            'purchase_order_number' => $this->purchase_order_number,
            'status' => $this->status,
            'order_date' => $this->order_date?->toDateString(),
            'expected_delivery_date' => $this->expected_delivery_date?->toDateString(),
            'currency' => $this->currency,
            'subtotal' => $this->subtotal,
            'discount_amount' => $this->discount_amount,
            'tax_amount' => $this->tax_amount,
            'total_amount' => $this->total_amount,
            'payment_terms' => $this->payment_terms,
            'delivery_terms' => $this->delivery_terms,
            'delivery_address' => $this->delivery_address,
            'notes' => $this->notes,
            'supplier' => [
                'id' => $this->supplier_id,
                'name' => $this->supplier?->name,
            ],
            'selected_quotation' => [
                'id' => $this->selectedQuotation?->id,
                'quotation_number' => $this->selectedQuotation?->quotation_number,
                'total_amount' => $this->selectedQuotation?->total_amount,
            ],
            'items' => PurchaseOrderItemResource::collection($this->whenLoaded('items')),
            'accountant_confirmed_at' => $this->accountant_confirmed_at?->toDateTimeString(),
            'issued_at' => $this->issued_at?->toDateTimeString(),
            'supplier_acknowledged_at' => $this->supplier_acknowledged_at?->toDateTimeString(),
            'rejected_at' => $this->rejected_at?->toDateTimeString(),
            'rejection_reason' => $this->rejection_reason,
            'cancelled_at' => $this->cancelled_at?->toDateTimeString(),
            'cancellation_reason' => $this->cancellation_reason,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];

        $data['requisition'] = $this->whenLoaded('requisition', function () use ($canSeeBudgetData) {
            $r = $this->requisition;

            $base = [
                'id' => $r->id,
                'requisition_number' => $r->requisition_number,
                'status' => $r->status,
                'purpose' => $r->purpose,
                'required_date' => $r->required_date?->toDateString(),
                'department' => [
                    'id' => $r->department?->id,
                    'name' => $r->department?->name,
                ],
                'business_entity' => [
                    'id' => $r->businessEntity?->id,
                    'name' => $r->businessEntity?->name,
                ],
            ];

            if ($canSeeBudgetData) {
                $base['estimated_amount'] = $r->estimated_amount;
                $base['committed_amount'] = $r->committed_amount;
                $base['estimate_difference_reason'] = $r->estimate_difference_reason;
            }

            return $base;
        });

        if ($canSeeBudgetData) {
            $data['quotation_recommendation'] = [
                'id' => $this->quotationRecommendation?->id,
                'total_quoted_amount' => $this->quotationRecommendation?->total_quoted_amount,
            ];
            $data['financial_year'] = [
                'id' => $this->financialYear?->id,
                'name' => $this->financialYear?->name,
            ];
        }

        return $data;
    }
}
