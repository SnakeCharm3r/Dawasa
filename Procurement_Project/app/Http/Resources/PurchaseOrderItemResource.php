<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderItemResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'purchase_requisition_item_id' => $this->purchase_requisition_item_id,
            'quotation_item_id' => $this->quotation_item_id,
            'item_name' => $this->item_name,
            'specification' => $this->specification,
            'quantity_ordered' => $this->quantity_ordered,
            'quantity_received' => $this->quantity_received,
            'unit' => $this->unit,
            'unit_price' => $this->unit_price,
            'line_total' => $this->line_total,
        ];
    }
}
