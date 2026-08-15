<?php

namespace App\Services;

use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PurchaseRequisitionService
{
    public function createDraft(User $requester, array $data): PurchaseRequisition
    {
        $requisition = null;
        DB::transaction(function () use ($requester, $data, &$requisition) {
            $requisition = PurchaseRequisition::create([
                'requisition_number' => $this->generateRequisitionNumber(),
                'business_entity_id' => $data['business_entity_id'],
                'department_id' => $data['department_id'],
                'requester_id' => $requester->id,
                'line_manager_id' => $requester->line_manager_id,
                'required_date' => $data['required_date'],
                'purpose' => $data['purpose'],
                'estimated_amount' => $data['estimated_amount'],
                'committed_amount' => 0,
                'status' => PurchaseRequisition::STATUS_DRAFT,
                'estimate_difference_reason' => $data['estimate_difference_reason'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                PurchaseRequisitionItem::create([
                    'purchase_requisition_id' => $requisition->id,
                    'item_name' => $item['item_name'],
                    'specification' => $item['specification'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'],
                    'estimated_unit_price' => $item['estimated_unit_price'] ?? null,
                    'estimated_total' => $item['estimated_total'],
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            if (! empty($data['attachments'])) {
                foreach ($data['attachments'] as $attachment) {
                    // attachments are handled separately by upload controller
                }
            }
        });

        return $requisition->load(['items']);
    }

    public function updateDraft(PurchaseRequisition $requisition, array $data): PurchaseRequisition
    {
        DB::transaction(function () use ($requisition, $data) {
            $requisition->update([
                'business_entity_id' => $data['business_entity_id'] ?? $requisition->business_entity_id,
                'department_id' => $data['department_id'] ?? $requisition->department_id,
                'required_date' => $data['required_date'] ?? $requisition->required_date,
                'purpose' => $data['purpose'] ?? $requisition->purpose,
                'estimated_amount' => $data['estimated_amount'] ?? $requisition->estimated_amount,
                'estimate_difference_reason' => $data['estimate_difference_reason'] ?? $requisition->estimate_difference_reason,
            ]);

            if (isset($data['items'])) {
                $requisition->items()->delete();
                foreach ($data['items'] as $item) {
                    PurchaseRequisitionItem::create([
                        'purchase_requisition_id' => $requisition->id,
                        'item_name' => $item['item_name'],
                        'specification' => $item['specification'] ?? null,
                        'quantity' => $item['quantity'],
                        'unit' => $item['unit'],
                        'estimated_unit_price' => $item['estimated_unit_price'] ?? null,
                        'estimated_total' => $item['estimated_total'],
                        'notes' => $item['notes'] ?? null,
                    ]);
                }
            }
        });

        return $requisition->fresh(['items']);
    }

    public function markQuotationsReady(PurchaseRequisition $requisition): PurchaseRequisition
    {
        if (! in_array($requisition->status, [
            PurchaseRequisition::STATUS_APPROVED_FOR_SOURCING,
            PurchaseRequisition::STATUS_RETURNED_TO_SOURCING,
        ], true)) {
            throw new \RuntimeException('Only requisitions that are approved for sourcing or returned to sourcing can be marked ready for quotation recommendations.');
        }

        if ($requisition->supplierQuotations()->valid()->doesntExist()) {
            throw new \RuntimeException('At least one valid supplier quotation is required before marking the requisition ready for recommendation.');
        }

        return DB::transaction(function () use ($requisition) {
            $requisition->update([
                'status' => PurchaseRequisition::STATUS_QUOTATIONS_READY,
            ]);

            return $requisition;
        });
    }

    private function generateRequisitionNumber(): string
    {
        $year = date('Y');
        $count = PurchaseRequisition::query()->whereYear('created_at', $year)->count() + 1;

        return sprintf('PR-%s-%06d', $year, $count);
    }
}
