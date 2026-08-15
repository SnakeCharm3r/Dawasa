<?php

namespace App\Services;

use App\Models\BusinessEntity;
use App\Models\EntityBudget;
use App\Models\FinancialYear;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderApproval;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequisition;
use App\Models\QuotationRecommendation;
use App\Models\SupplierQuotation;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    public function createDraftFromRequisition(PurchaseRequisition $requisition, User $actor, array $operational = []): PurchaseOrder
    {
        return DB::transaction(function () use ($requisition, $actor, $operational) {
            if ($requisition->status !== PurchaseRequisition::STATUS_APPROVED_FOR_PURCHASE) {
                throw new \RuntimeException('A purchase order can only be created for a requisition that is approved for purchase.');
            }

            if (PurchaseOrder::where('purchase_requisition_id', $requisition->id)->exists()) {
                throw new \RuntimeException('A purchase order already exists for this requisition.');
            }

            $recommendation = QuotationRecommendation::where('purchase_requisition_id', $requisition->id)
                ->where('status', QuotationRecommendation::STATUS_APPROVED)
                ->first();

            if (! $recommendation) {
                throw new \RuntimeException('No GM-approved quotation recommendation was found for this requisition.');
            }

            $quotation = $recommendation->selectedQuotation;

            if (! $quotation || $quotation->status !== SupplierQuotation::STATUS_ACTIVE) {
                throw new \RuntimeException('The selected supplier quotation is no longer valid.');
            }

            $financialYear = FinancialYear::where('is_active', true)->first();

            if (! $financialYear) {
                throw new \RuntimeException('No active financial year exists for this purchase order.');
            }

            $budget = EntityBudget::where('business_entity_id', $requisition->business_entity_id)
                ->where('financial_year_id', $financialYear->id)
                ->where('status', EntityBudget::STATUS_APPROVED)
                ->first();

            if (! $budget) {
                throw new \RuntimeException('No approved entity budget was found for the requisition business entity and active financial year.');
            }

            $order = new PurchaseOrder([
                'purchase_requisition_id' => $requisition->id,
                'supplier_id' => $quotation->supplier_id,
                'quotation_recommendation_id' => $recommendation->id,
                'selected_quotation_id' => $quotation->id,
                'business_entity_id' => $requisition->business_entity_id,
                'financial_year_id' => $budget->financial_year_id,
                'status' => PurchaseOrder::STATUS_DRAFT,
                'order_date' => $operational['order_date'] ?? now()->toDateString(),
                'expected_delivery_date' => $operational['expected_delivery_date'] ?? null,
                'currency' => $operational['currency'] ?? 'TZS',
                'discount_amount' => 0,
                'tax_amount' => 0,
                'payment_terms' => $operational['payment_terms'] ?? null,
                'delivery_terms' => $operational['delivery_terms'] ?? null,
                'delivery_address' => $operational['delivery_address'] ?? null,
                'notes' => $operational['notes'] ?? null,
            ]);

            $subtotal = 0;
            $items = [];

            foreach ($quotation->items as $quotationItem) {
                $lineTotal = (float) $quotationItem->quantity * (float) $quotationItem->unit_price;
                $subtotal += $lineTotal;

                $items[] = new PurchaseOrderItem([
                    'purchase_requisition_item_id' => $this->matchRequisitionItem($requisition, $quotationItem->item_name),
                    'quotation_item_id' => $quotationItem->id,
                    'item_name' => $quotationItem->item_name,
                    'specification' => $quotationItem->specification,
                    'quantity_ordered' => $quotationItem->quantity,
                    'quantity_received' => 0,
                    'unit' => $quotationItem->unit,
                    'unit_price' => $quotationItem->unit_price,
                    'line_total' => number_format($lineTotal, 2, '.', ''),
                ]);
            }

            $order->subtotal = number_format($subtotal, 2, '.', '');
            $order->total_amount = number_format($subtotal, 2, '.', '');
            $order->save();
            $order->items()->saveMany($items);

            PurchaseOrderApproval::create([
                'purchase_order_id' => $order->id,
                'action' => PurchaseOrderApproval::ACTION_CREATED,
                'actor_id' => $actor->id,
                'comments' => null,
                'action_at' => now(),
            ]);

            ActivityLog::record($actor, 'purchase_order.created', $order, [], ['status' => PurchaseOrder::STATUS_DRAFT]);

            return $order;
        });
    }

    protected function matchRequisitionItem(PurchaseRequisition $requisition, string $itemName): ?int
    {
        return $requisition->items()->where('item_name', $itemName)->value('id');
    }

    public function updateDraft(PurchaseOrder $order, User $actor, array $operational): PurchaseOrder
    {
        if ($order->status !== PurchaseOrder::STATUS_DRAFT) {
            throw new \RuntimeException('Only draft purchase orders can be updated.');
        }

        return DB::transaction(function () use ($order, $actor, $operational) {
            $order->update(array_filter($operational, fn ($value) => $value !== null));

            ActivityLog::record($actor, 'purchase_order.updated', $order, [], ['status' => $order->status]);

            return $order->fresh();
        });
    }

    public function submitForConfirmation(PurchaseOrder $order, User $actor, ?string $comments): PurchaseOrder
    {
        if ($order->status !== PurchaseOrder::STATUS_DRAFT) {
            throw new \RuntimeException('Only draft purchase orders can be submitted for confirmation.');
        }

        return DB::transaction(function () use ($order, $actor, $comments) {
            $order->update(['status' => PurchaseOrder::STATUS_PENDING_ACCOUNTANT_CONFIRMATION]);

            PurchaseOrderApproval::create([
                'purchase_order_id' => $order->id,
                'action' => PurchaseOrderApproval::ACTION_SUBMITTED_FOR_CONFIRMATION,
                'actor_id' => $actor->id,
                'comments' => $comments,
                'action_at' => now(),
            ]);

            ActivityLog::record(
                $actor,
                'purchase_order.submitted_for_confirmation',
                $order,
                ['status' => PurchaseOrder::STATUS_DRAFT],
                ['status' => PurchaseOrder::STATUS_PENDING_ACCOUNTANT_CONFIRMATION, 'comments' => $comments]
            );

            return $order->fresh();
        });
    }

    public function confirm(PurchaseOrder $order, User $actor, ?string $comments): PurchaseOrder
    {
        if ($order->status !== PurchaseOrder::STATUS_PENDING_ACCOUNTANT_CONFIRMATION) {
            throw new \RuntimeException('Only purchase orders pending accountant confirmation can be confirmed.');
        }

        if ($actor->id === $order->preparerId()) {
            throw new \RuntimeException('An accountant cannot confirm a purchase order they prepared.');
        }

        return DB::transaction(function () use ($order, $actor, $comments) {
            $budget = EntityBudget::where('business_entity_id', $order->business_entity_id)
                ->where('financial_year_id', $order->financial_year_id)
                ->where('status', EntityBudget::STATUS_APPROVED)
                ->lockForUpdate()
                ->first();

            if (! $budget) {
                throw new \RuntimeException('The approved entity budget for this purchase order is no longer valid.');
            }

            if ((float) $budget->available_amount < 0) {
                throw new \RuntimeException('The entity budget no longer has sufficient available balance for this commitment.');
            }

            $order->update([
                'status' => PurchaseOrder::STATUS_CONFIRMED,
                'accountant_confirmed_by' => $actor->id,
                'accountant_confirmed_at' => now(),
            ]);

            PurchaseOrderApproval::create([
                'purchase_order_id' => $order->id,
                'action' => PurchaseOrderApproval::ACTION_ACCOUNTANT_CONFIRMED,
                'actor_id' => $actor->id,
                'comments' => $comments,
                'action_at' => now(),
            ]);

            ActivityLog::record(
                $actor,
                'purchase_order.accountant_confirmed',
                $order,
                ['status' => PurchaseOrder::STATUS_PENDING_ACCOUNTANT_CONFIRMATION],
                ['status' => PurchaseOrder::STATUS_CONFIRMED, 'comments' => $comments]
            );

            return $order->fresh();
        });
    }

    public function returnToProcurement(PurchaseOrder $order, User $actor, string $comments): PurchaseOrder
    {
        if ($order->status !== PurchaseOrder::STATUS_PENDING_ACCOUNTANT_CONFIRMATION) {
            throw new \RuntimeException('Only purchase orders pending accountant confirmation can be returned.');
        }

        return DB::transaction(function () use ($order, $actor, $comments) {
            $order->update(['status' => PurchaseOrder::STATUS_DRAFT]);

            ActivityLog::record(
                $actor,
                'purchase_order.returned_to_procurement',
                $order,
                ['status' => PurchaseOrder::STATUS_PENDING_ACCOUNTANT_CONFIRMATION],
                ['status' => PurchaseOrder::STATUS_DRAFT, 'comments' => $comments]
            );

            return $order->fresh();
        });
    }

    public function issue(PurchaseOrder $order, User $actor): PurchaseOrder
    {
        if ($order->status !== PurchaseOrder::STATUS_CONFIRMED) {
            throw new \RuntimeException('Only accountant-confirmed purchase orders can be issued.');
        }

        return DB::transaction(function () use ($order, $actor) {
            $order->update([
                'status' => PurchaseOrder::STATUS_ISSUED,
                'issued_by' => $actor->id,
                'issued_at' => now(),
                'purchase_order_number' => $this->generateOrderNumber($order),
            ]);

            PurchaseOrderApproval::create([
                'purchase_order_id' => $order->id,
                'action' => PurchaseOrderApproval::ACTION_ISSUED,
                'actor_id' => $actor->id,
                'comments' => null,
                'action_at' => now(),
            ]);

            ActivityLog::record(
                $actor,
                'purchase_order.issued',
                $order,
                ['status' => PurchaseOrder::STATUS_CONFIRMED],
                ['status' => PurchaseOrder::STATUS_ISSUED, 'purchase_order_number' => $order->purchase_order_number]
            );

            return $order->fresh();
        });
    }

    public function cancel(PurchaseOrder $order, User $actor, string $reason): PurchaseOrder
    {
        if (! $order->canBeCancelled()) {
            throw new \RuntimeException('This purchase order cannot be cancelled at its current stage.');
        }

        return DB::transaction(function () use ($order, $actor, $reason) {
            $oldStatus = $order->status;

            $order->update([
                'status' => PurchaseOrder::STATUS_CANCELLED,
                'cancelled_by' => $actor->id,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            PurchaseOrderApproval::create([
                'purchase_order_id' => $order->id,
                'action' => PurchaseOrderApproval::ACTION_CANCELLED,
                'actor_id' => $actor->id,
                'comments' => $reason,
                'action_at' => now(),
            ]);

            ActivityLog::record(
                $actor,
                'purchase_order.cancelled',
                $order,
                ['status' => $oldStatus],
                ['status' => PurchaseOrder::STATUS_CANCELLED, 'cancellation_reason' => $reason]
            );

            return $order->fresh();
        });
    }

    public function acknowledge(PurchaseOrder $order, User $actor, ?string $reference): PurchaseOrder
    {
        if ($order->status !== PurchaseOrder::STATUS_ISSUED) {
            throw new \RuntimeException('Only issued purchase orders can be acknowledged.');
        }

        return DB::transaction(function () use ($order, $actor, $reference) {
            $order->update(['supplier_acknowledged_at' => now()]);

            PurchaseOrderApproval::create([
                'purchase_order_id' => $order->id,
                'action' => PurchaseOrderApproval::ACTION_ACKNOWLEDGED,
                'actor_id' => $actor->id,
                'comments' => $reference,
                'action_at' => now(),
            ]);

            ActivityLog::record(
                $actor,
                'purchase_order.acknowledged',
                $order,
                [],
                ['supplier_acknowledged_at' => $order->supplier_acknowledged_at?->toDateTimeString(), 'acknowledgement_reference' => $reference]
            );

            return $order->fresh();
        });
    }

    public function generateOrderNumber(PurchaseOrder $order): string
    {
        $year = $order->order_date ? $order->order_date->year : now()->year;
        $count = PurchaseOrder::whereYear('order_date', $year)
            ->whereNotNull('purchase_order_number')
            ->count();

        return 'PO-'.$year.'-'.str_pad($count + 1, 5, '0', STR_PAD_LEFT);
    }
}
