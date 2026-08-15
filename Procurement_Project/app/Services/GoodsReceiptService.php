<?php

namespace App\Services;

use App\Models\GoodsReceiptApproval;
use App\Models\GoodsReceiptNote;
use App\Models\GoodsReceiptNoteItem;
use App\Models\ActivityLog;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class GoodsReceiptService
{
    public function createDraftFromPurchaseOrder(PurchaseOrder $order, User $actor, array $data): GoodsReceiptNote
    {
        return DB::transaction(function () use ($order, $actor, $data) {
            if (! in_array($order->status, [
                PurchaseOrder::STATUS_ISSUED,
                PurchaseOrder::STATUS_ACKNOWLEDGED,
                PurchaseOrder::STATUS_PARTIALLY_RECEIVED,
            ], true)) {
                throw new \RuntimeException('GRN can only be created for issued, acknowledged, or partially received purchase orders.');
            }

            if (in_array($order->status, [
                PurchaseOrder::STATUS_CANCELLED,
                PurchaseOrder::STATUS_CLOSED,
                PurchaseOrder::STATUS_FULLY_RECEIVED,
            ], true)) {
                throw new \RuntimeException('Cannot create GRN for a cancelled, closed, or fully received purchase order.');
            }

            if (empty($data['items']) || count($data['items']) === 0) {
                throw new \RuntimeException('GRN must contain at least one item.');
            }

            $grn = new GoodsReceiptNote([
                'purchase_order_id' => $order->id,
                'supplier_id' => $order->supplier_id,
                'business_entity_id' => $order->business_entity_id,
                'received_by' => $actor->id,
                'received_date' => $data['received_date'] ?? now()->toDateString(),
                'delivery_note_number' => $data['delivery_note_number'] ?? null,
                'supplier_invoice_reference' => $data['supplier_invoice_reference'] ?? null,
                'delivery_condition' => $data['delivery_condition'] ?? 'good',
                'status' => GoodsReceiptNote::STATUS_DRAFT,
                'inspection_required' => $data['inspection_required'] ?? true,
                'received_location' => $data['received_location'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $grn->save();

            $items = [];
            foreach ($data['items'] as $itemData) {
                $poItem = PurchaseOrderItem::findOrFail($itemData['purchase_order_item_id']);

                if ($poItem->purchase_order_id !== $order->id) {
                    throw new \RuntimeException('Item does not belong to the specified purchase order.');
                }

                $outstanding = $this->calculateOutstandingQuantity($poItem);

                if ((float) $itemData['quantity_received'] <= 0) {
                    throw new \RuntimeException('Quantity received must be greater than zero.');
                }

                if ((float) $itemData['quantity_received'] > $outstanding) {
                    throw new \RuntimeException('Quantity received cannot exceed outstanding PO quantity.');
                }

                $items[] = new GoodsReceiptNoteItem([
                    'purchase_order_item_id' => $poItem->id,
                    'item_name' => $poItem->item_name,
                    'specification' => $poItem->specification,
                    'quantity_ordered' => $poItem->quantity_ordered,
                    'quantity_previously_received' => $poItem->quantity_received,
                    'quantity_received' => $itemData['quantity_received'],
                    'quantity_accepted' => 0,
                    'quantity_rejected' => 0,
                    'unit' => $poItem->unit,
                    'condition_status' => GoodsReceiptNoteItem::CONDITION_PENDING,
                ]);
            }

            $grn->items()->saveMany($items);

            GoodsReceiptApproval::create([
                'goods_receipt_note_id' => $grn->id,
                'action' => GoodsReceiptApproval::ACTION_CREATED,
                'actor_id' => $actor->id,
                'comments' => null,
                'action_at' => now(),
            ]);

            ActivityLog::record($actor, 'grn.created', $grn, [], ['status' => GoodsReceiptNote::STATUS_DRAFT]);

            return $grn->fresh();
        });
    }

    public function updateDraft(GoodsReceiptNote $grn, User $actor, array $data): GoodsReceiptNote
    {
        if ($grn->status !== GoodsReceiptNote::STATUS_DRAFT) {
            throw new \RuntimeException('Only draft GRNs can be updated.');
        }

        return DB::transaction(function () use ($grn, $actor, $data) {
            $grn->update([
                'received_date' => $data['received_date'] ?? $grn->received_date,
                'delivery_note_number' => $data['delivery_note_number'] ?? $grn->delivery_note_number,
                'supplier_invoice_reference' => $data['supplier_invoice_reference'] ?? $grn->supplier_invoice_reference,
                'delivery_condition' => $data['delivery_condition'] ?? $grn->delivery_condition,
                'inspection_required' => $data['inspection_required'] ?? $grn->inspection_required,
                'received_location' => $data['received_location'] ?? $grn->received_location,
                'notes' => $data['notes'] ?? $grn->notes,
            ]);

            if (! empty($data['items'])) {
                foreach ($data['items'] as $itemData) {
                    $grnItem = GoodsReceiptNoteItem::findOrFail($itemData['id']);
                    $poItem = PurchaseOrderItem::findOrFail($grnItem->purchase_order_item_id);

                    $outstanding = $this->calculateOutstandingQuantity($poItem);

                    if ((float) $itemData['quantity_received'] <= 0) {
                        throw new \RuntimeException('Quantity received must be greater than zero.');
                    }

                    if ((float) $itemData['quantity_received'] > $outstanding) {
                        throw new \RuntimeException('Quantity received cannot exceed outstanding PO quantity.');
                    }

                    $grnItem->update([
                        'quantity_received' => $itemData['quantity_received'],
                    ]);
                }
            }

            ActivityLog::record($actor, 'grn.updated', $grn, [], ['status' => $grn->status]);

            return $grn->fresh();
        });
    }

    public function submit(GoodsReceiptNote $grn, User $actor): GoodsReceiptNote
    {
        if ($grn->status !== GoodsReceiptNote::STATUS_DRAFT) {
            throw new \RuntimeException('Only draft GRNs can be submitted.');
        }

        return DB::transaction(function () use ($grn, $actor) {
            $grn->update([
                'status' => GoodsReceiptNote::STATUS_SUBMITTED,
                'submitted_at' => now(),
            ]);

            GoodsReceiptApproval::create([
                'goods_receipt_note_id' => $grn->id,
                'action' => GoodsReceiptApproval::ACTION_SUBMITTED,
                'actor_id' => $actor->id,
                'comments' => null,
                'action_at' => now(),
            ]);

            ActivityLog::record(
                $actor,
                'grn.submitted',
                $grn,
                ['status' => GoodsReceiptNote::STATUS_DRAFT],
                ['status' => GoodsReceiptNote::STATUS_SUBMITTED]
            );

            return $grn->fresh();
        });
    }

    public function inspect(GoodsReceiptNote $grn, User $actor, array $inspectionData): GoodsReceiptNote
    {
        if ($grn->status !== GoodsReceiptNote::STATUS_SUBMITTED) {
            throw new \RuntimeException('Only submitted GRNs can be inspected.');
        }

        if ($actor->id === $grn->received_by) {
            throw new \RuntimeException('A user cannot inspect their own GRN.');
        }

        return DB::transaction(function () use ($grn, $actor, $inspectionData) {
            $order = PurchaseOrder::lockForUpdate()->findOrFail($grn->purchase_order_id);

            $allAccepted = true;
            $allRejected = true;
            $hasPartial = false;

            foreach ($inspectionData['items'] as $itemData) {
                $grnItem = GoodsReceiptNoteItem::findOrFail($itemData['id']);
                $poItem = PurchaseOrderItem::lockForUpdate()->findOrFail($grnItem->purchase_order_item_id);

                $quantityAccepted = (float) ($itemData['quantity_accepted'] ?? 0);
                $quantityRejected = (float) ($itemData['quantity_rejected'] ?? 0);
                $quantityReceived = (float) $grnItem->quantity_received;

                if ($quantityAccepted + $quantityRejected !== $quantityReceived) {
                    throw new \RuntimeException('Accepted and rejected quantities must equal received quantity.');
                }

                if ($quantityRejected > 0 && empty($itemData['rejection_reason']) && empty($itemData['inspection_notes'])) {
                    throw new \RuntimeException('Rejected items require a rejection reason or inspection notes.');
                }

                $grnItem->update([
                    'quantity_accepted' => $quantityAccepted,
                    'quantity_rejected' => $quantityRejected,
                    'condition_status' => $itemData['condition_status'],
                    'rejection_reason' => $itemData['rejection_reason'] ?? null,
                    'inspection_notes' => $itemData['inspection_notes'] ?? null,
                ]);

                if ($quantityAccepted > 0) {
                    $allRejected = false;
                }

                if ($quantityRejected > 0) {
                    $allAccepted = false;
                }

                if ($quantityAccepted > 0 && $quantityRejected > 0) {
                    $hasPartial = true;
                }

                $poItem->quantity_received += $quantityAccepted;
                $poItem->save();
            }

            $grn->update([
                'status' => $allAccepted ? GoodsReceiptNote::STATUS_ACCEPTED : ($allRejected ? GoodsReceiptNote::STATUS_REJECTED : GoodsReceiptNote::STATUS_PARTIALLY_ACCEPTED),
                'inspected_by' => $actor->id,
                'inspected_at' => now(),
                'inspection_comments' => $inspectionData['inspection_comments'] ?? null,
            ]);

            $action = $allAccepted ? GoodsReceiptApproval::ACTION_ACCEPTED : ($allRejected ? GoodsReceiptApproval::ACTION_REJECTED : GoodsReceiptApproval::ACTION_PARTIALLY_ACCEPTED);

            GoodsReceiptApproval::create([
                'goods_receipt_note_id' => $grn->id,
                'action' => $action,
                'actor_id' => $actor->id,
                'comments' => $inspectionData['inspection_comments'] ?? null,
                'action_at' => now(),
            ]);

            $this->updatePurchaseOrderStatus($order);

            ActivityLog::record(
                $actor,
                'grn.inspected',
                $grn,
                ['status' => GoodsReceiptNote::STATUS_SUBMITTED],
                ['status' => $grn->status, 'inspected_by' => $actor->id]
            );

            return $grn->fresh();
        });
    }

    public function cancel(GoodsReceiptNote $grn, User $actor, string $reason): GoodsReceiptNote
    {
        if (! $grn->canBeCancelled()) {
            throw new \RuntimeException('Only draft GRNs can be cancelled.');
        }

        return DB::transaction(function () use ($grn, $actor, $reason) {
            $grn->update([
                'status' => GoodsReceiptNote::STATUS_CANCELLED,
                'cancelled_by' => $actor->id,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            GoodsReceiptApproval::create([
                'goods_receipt_note_id' => $grn->id,
                'action' => GoodsReceiptApproval::ACTION_CANCELLED,
                'actor_id' => $actor->id,
                'comments' => $reason,
                'action_at' => now(),
            ]);

            ActivityLog::record(
                $actor,
                'grn.cancelled',
                $grn,
                ['status' => GoodsReceiptNote::STATUS_DRAFT],
                ['status' => GoodsReceiptNote::STATUS_CANCELLED, 'cancellation_reason' => $reason]
            );

            return $grn->fresh();
        });
    }

    protected function calculateOutstandingQuantity(PurchaseOrderItem $poItem): float
    {
        $acceptedQuantity = GoodsReceiptNoteItem::where('purchase_order_item_id', $poItem->id)
            ->whereIn('condition_status', [
                GoodsReceiptNoteItem::CONDITION_ACCEPTED,
                GoodsReceiptNoteItem::CONDITION_PARTIALLY_ACCEPTED,
            ])
            ->sum('quantity_accepted');

        return (float) $poItem->quantity_ordered - (float) $acceptedQuantity;
    }

    protected function updatePurchaseOrderStatus(PurchaseOrder $order): void
    {
        $allItems = $order->items;
        $allFullyReceived = true;
        $hasPartial = false;

        foreach ($allItems as $item) {
            if ((float) $item->quantity_received < (float) $item->quantity_ordered) {
                $allFullyReceived = false;
            }

            if ((float) $item->quantity_received > 0 && (float) $item->quantity_received < (float) $item->quantity_ordered) {
                $hasPartial = true;
            }
        }

        if ($allFullyReceived) {
            $order->status = PurchaseOrder::STATUS_FULLY_RECEIVED;
        } elseif ($hasPartial) {
            $order->status = PurchaseOrder::STATUS_PARTIALLY_RECEIVED;
        }

        $order->save();
    }

    public function generateGrnNumber(GoodsReceiptNote $grn): string
    {
        $year = $grn->received_date ? $grn->received_date->year : now()->year;
        $count = GoodsReceiptNote::whereYear('received_date', $year)
            ->whereNotNull('grn_number')
            ->count();

        return 'GRN-'.$year.'-'.str_pad($count + 1, 6, '0', STR_PAD_LEFT);
    }
}
