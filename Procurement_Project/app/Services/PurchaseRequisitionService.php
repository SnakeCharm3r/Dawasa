<?php

namespace App\Services;

use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseRequisitionService
{
    public function createDraft(User $requester, array $data): PurchaseRequisition
    {
        $lineManager = $this->resolveLineManager($requester);
        $requisition = null;
        DB::transaction(function () use ($requester, $lineManager, $data, &$requisition) {
            $requisition = PurchaseRequisition::create([
                'requisition_number' => $this->generateRequisitionNumber(),
                'business_entity_id' => $data['business_entity_id'],
                'department_id' => $data['department_id'],
                'supplier_category_id' => $data['supplier_category_id'],
                'requester_id' => $requester->id,
                'line_manager_id' => $lineManager->id,
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
                    'estimated_total' => $this->lineTotal($item),
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

    public function resolveLineManager(User $requester): User
    {
        $lineManager = $requester->assignedLineManagerInDepartment();

        if (! $lineManager) {
            throw ValidationException::withMessages([
                'line_manager_id' => 'Assign an active line manager from the requester\'s department before creating or submitting this requisition.',
            ]);
        }

        return $lineManager;
    }

    public function updateDraft(PurchaseRequisition $requisition, array $data): PurchaseRequisition
    {
        DB::transaction(function () use ($requisition, $data) {
            $requisition->update([
                'business_entity_id' => $data['business_entity_id'] ?? $requisition->business_entity_id,
                'department_id' => $data['department_id'] ?? $requisition->department_id,
                'supplier_category_id' => $data['supplier_category_id'] ?? $requisition->supplier_category_id,
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
                        'estimated_total' => $this->lineTotal($item),
                        'notes' => $item['notes'] ?? null,
                    ]);
                }
            }
        });

        return $requisition->fresh(['items']);
    }

    private function lineTotal(array $item): float
    {
        if (isset($item['estimated_unit_price'])) {
            return round((float) $item['quantity'] * (float) $item['estimated_unit_price'], 2);
        }

        return (float) $item['estimated_total'];
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
