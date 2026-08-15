<?php

namespace App\Services;

use App\Models\GoodsReceiptNoteItem;
use App\Models\InvoiceMatchRecord;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SupplierInvoice;
use App\Models\SupplierInvoiceItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ThreeWayMatchingService
{
    public function calculateInvoiceableQuantity(PurchaseOrderItem $poItem): float
    {
        $acceptedGrnQuantity = GoodsReceiptNoteItem::where('purchase_order_item_id', $poItem->id)
            ->whereIn('condition_status', [
                GoodsReceiptNoteItem::CONDITION_ACCEPTED,
                GoodsReceiptNoteItem::CONDITION_PARTIALLY_ACCEPTED,
            ])
            ->sum('quantity_accepted');

        $previouslyInvoiced = SupplierInvoiceItem::where('purchase_order_item_id', $poItem->id)
            ->sum('quantity_invoiced');

        return max(0, (float) $acceptedGrnQuantity - (float) $previouslyInvoiced);
    }

    public function performThreeWayMatch(SupplierInvoice $invoice, User $actor): InvoiceMatchRecord
    {
        return DB::transaction(function () use ($invoice, $actor) {
            $po = PurchaseOrder::lockForUpdate()->findOrFail($invoice->purchase_order_id);

            if (! in_array($po->status, [
                PurchaseOrder::STATUS_ISSUED,
                PurchaseOrder::STATUS_ACKNOWLEDGED,
                PurchaseOrder::STATUS_PARTIALLY_RECEIVED,
                PurchaseOrder::STATUS_FULLY_RECEIVED,
            ], true)) {
                throw new \RuntimeException('Invoice can only be matched against issued, acknowledged, or received purchase orders.');
            }

            if ($invoice->supplier_id !== $po->supplier_id) {
                throw new \RuntimeException('Invoice supplier must match the purchase order supplier.');
            }

            if ($invoice->business_entity_id !== $po->business_entity_id) {
                throw new \RuntimeException('Invoice business entity must match the purchase order business entity.');
            }

            if ($invoice->financial_year_id !== $po->financial_year_id) {
                throw new \RuntimeException('Invoice financial year must match the purchase order financial year.');
            }

            $poTotal = $po->items->sum(fn ($item) => $item->quantity_ordered * $item->unit_price);
            $grnAcceptedTotal = 0;
            $varianceAmount = 0;
            $varianceReasons = [];

            foreach ($invoice->items as $invoiceItem) {
                $poItem = PurchaseOrderItem::findOrFail($invoiceItem->purchase_order_item_id);

                $invoiceableQty = $this->calculateInvoiceableQuantity($poItem);

                if ((float) $invoiceItem->quantity_invoiced > $invoiceableQty) {
                    throw new \RuntimeException('Invoice quantity cannot exceed accepted but uninvoiced GRN quantities.');
                }

                $grnAcceptedQty = GoodsReceiptNoteItem::where('purchase_order_item_id', $poItem->id)
                    ->whereIn('condition_status', [
                        GoodsReceiptNoteItem::CONDITION_ACCEPTED,
                        GoodsReceiptNoteItem::CONDITION_PARTIALLY_ACCEPTED,
                    ])
                    ->sum('quantity_accepted');

                $grnAcceptedTotal += $grnAcceptedQty * $poItem->unit_price;

                $priceVariance = abs((float) $invoiceItem->unit_price - (float) $poItem->unit_price);
                if ($priceVariance > 0.01) {
                    $varianceAmount += $priceVariance * (float) $invoiceItem->quantity_invoiced;
                    $varianceReasons[] = "Price variance on item {$poItem->item_name}: PO {$poItem->unit_price} vs Invoice {$invoiceItem->unit_price}";
                }

                $quantityVariance = abs((float) $invoiceItem->quantity_invoiced - $grnAcceptedQty);
                if ($quantityVariance > 0.01) {
                    $varianceAmount += $quantityVariance * (float) $poItem->unit_price;
                    $varianceReasons[] = "Quantity variance on item {$poItem->item_name}: GRN {$grnAcceptedQty} vs Invoice {$invoiceItem->quantity_invoiced}";
                }
            }

            $invoiceTotal = $invoice->total_amount;
            $totalVariance = abs($invoiceTotal - $grnAcceptedTotal);

            if (count($varianceReasons) > 0 || $totalVariance > 0.01) {
                $matchStatus = InvoiceMatchRecord::MATCH_STATUS_QUANTITY_VARIANCE;
                if (str_contains(implode(' ', $varianceReasons), 'Price variance')) {
                    $matchStatus = InvoiceMatchRecord::MATCH_STATUS_PRICE_VARIANCE;
                }
                $invoice->status = SupplierInvoice::STATUS_MATCHED_WITH_VARIANCE;
            } else {
                $matchStatus = InvoiceMatchRecord::MATCH_STATUS_MATCHED;
                $invoice->status = SupplierInvoice::STATUS_MATCHED;
            }

            $invoice->matched_amount = $grnAcceptedTotal;
            $invoice->save();

            $matchRecord = InvoiceMatchRecord::create([
                'supplier_invoice_id' => $invoice->id,
                'purchase_order_id' => $po->id,
                'goods_receipt_note_id' => null,
                'match_status' => $matchStatus,
                'po_amount' => $poTotal,
                'grn_accepted_amount' => $grnAcceptedTotal,
                'invoice_amount' => $invoiceTotal,
                'variance_amount' => $totalVariance,
                'variance_reason' => count($varianceReasons) > 0 ? implode('; ', $varianceReasons) : null,
                'matched_by' => $actor->id,
                'matched_at' => now(),
            ]);

            ActivityLog::record(
                $actor,
                'invoice.matched',
                $invoice,
                ['status' => SupplierInvoice::STATUS_PENDING_MATCH],
                ['status' => $invoice->status, 'match_status' => $matchStatus]
            );

            return $matchRecord;
        });
    }

    public function returnForCorrection(SupplierInvoice $invoice, User $actor, string $reason): SupplierInvoice
    {
        if ($invoice->status !== SupplierInvoice::STATUS_MATCHED_WITH_VARIANCE) {
            throw new \RuntimeException('Only invoices with a variance can be returned for correction.');
        }

        return DB::transaction(function () use ($invoice, $actor, $reason) {
            $invoice->status = SupplierInvoice::STATUS_RETURNED;
            $invoice->rejection_reason = $reason;
            $invoice->save();

            ActivityLog::record(
                $actor,
                'invoice.returned',
                $invoice,
                ['status' => $invoice->getOriginal('status')],
                ['status' => SupplierInvoice::STATUS_RETURNED, 'reason' => $reason]
            );

            return $invoice->fresh();
        });
    }

    public function approveVariance(SupplierInvoice $invoice, User $actor): SupplierInvoice
    {
        if ($invoice->status !== SupplierInvoice::STATUS_MATCHED_WITH_VARIANCE) {
            throw new \RuntimeException('Only invoices with variance status can be approved.');
        }

        return DB::transaction(function () use ($invoice, $actor) {
            $invoice->status = SupplierInvoice::STATUS_APPROVED_FOR_PAYMENT;
            $invoice->approved_by = $actor->id;
            $invoice->approved_at = now();
            $invoice->save();

            ActivityLog::record(
                $actor,
                'invoice.variance_approved',
                $invoice,
                ['status' => SupplierInvoice::STATUS_MATCHED_WITH_VARIANCE],
                ['status' => SupplierInvoice::STATUS_APPROVED_FOR_PAYMENT, 'approved_by' => $actor->id]
            );

            return $invoice->fresh();
        });
    }
}
