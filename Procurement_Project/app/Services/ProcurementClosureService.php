<?php

namespace App\Services;

use App\Models\GoodsReceiptNote;
use App\Models\PaymentVoucher;
use App\Models\ProcurementClosure;
use App\Models\ProcurementClosureApproval;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\SupplierInvoice;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProcurementClosureService
{
    public function __construct(
        private readonly SupplierPerformanceService $supplierPerformanceService,
    ) {}

    protected function recordApproval(ProcurementClosure $closure, string $action, User $actor, ?string $comments = null): ProcurementClosureApproval
    {
        return ProcurementClosureApproval::create([
            'procurement_closure_id' => $closure->id,
            'action' => $action,
            'actor_id' => $actor->id,
            'comments' => $comments,
            'action_at' => now(),
        ]);
    }

    public function validateClosureEligibility(PurchaseRequisition $requisition): array
    {
        $issues = [];

        if ($requisition->status === PurchaseRequisition::STATUS_CANCELLED) {
            return ['can_close_as_cancelled' => true];
        }

        $po = $requisition->purchaseOrder;
        if (! $po) {
            $issues[] = 'No purchase order exists for this requisition.';

            return ['eligible' => false, 'issues' => $issues];
        }

        if ($po->status === PurchaseOrder::STATUS_CANCELLED) {
            return ['can_close_as_cancelled' => true];
        }

        if (! in_array($po->status, [
            PurchaseOrder::STATUS_FULLY_RECEIVED,
            PurchaseOrder::STATUS_CLOSED,
        ], true)) {
            $issues[] = 'Purchase order is not fully received.';
        }

        if ($po->status === PurchaseOrder::STATUS_PARTIALLY_RECEIVED) {
            $issues[] = 'Purchase order is only partially received.';
        }

        $invoices = SupplierInvoice::where('purchase_order_id', $po->id)->get();
        foreach ($invoices as $invoice) {
            if (! in_array($invoice->status, [
                SupplierInvoice::STATUS_PAID,
                SupplierInvoice::STATUS_CANCELLED,
            ], true)) {
                $issues[] = "Invoice {$invoice->invoice_number} is not paid or cancelled.";
            }

            if ($invoice->status === SupplierInvoice::STATUS_MATCHED_WITH_VARIANCE) {
                $issues[] = "Invoice {$invoice->invoice_number} has an unresolved variance.";
            }
        }

        $pendingVouchers = PaymentVoucher::whereHas('supplierInvoice', fn ($q) => $q->where('purchase_order_id', $po->id))
            ->whereIn('status', [
                PaymentVoucher::STATUS_DRAFT,
                PaymentVoucher::STATUS_SUBMITTED,
                PaymentVoucher::STATUS_PENDING_APPROVAL,
                PaymentVoucher::STATUS_APPROVED,
            ])
            ->exists();

        if ($pendingVouchers) {
            $issues[] = 'There are pending payment vouchers.';
        }

        $rejectedGRNs = GoodsReceiptNote::where('purchase_order_id', $po->id)
            ->where('status', GoodsReceiptNote::STATUS_REJECTED)
            ->exists();

        if ($rejectedGRNs) {
            $issues[] = 'There are rejected goods receipt notes.';
        }

        return [
            'eligible' => empty($issues),
            'issues' => $issues,
            'can_close_as_cancelled' => false,
        ];
    }

    public function identifyUnresolvedObligations(PurchaseOrder $po): array
    {
        $obligations = [];

        $invoices = SupplierInvoice::where('purchase_order_id', $po->id)
            ->whereNotIn('status', [
                SupplierInvoice::STATUS_PAID,
                SupplierInvoice::STATUS_CANCELLED,
            ])
            ->get();

        foreach ($invoices as $invoice) {
            $obligations[] = [
                'type' => 'unpaid_invoice',
                'description' => "Invoice {$invoice->invoice_number} - {$invoice->status}",
                'amount' => $invoice->outstanding_amount,
            ];
        }

        $vouchers = PaymentVoucher::whereHas('supplierInvoice', fn ($q) => $q->where('purchase_order_id', $po->id))
            ->whereIn('status', [
                PaymentVoucher::STATUS_DRAFT,
                PaymentVoucher::STATUS_SUBMITTED,
                PaymentVoucher::STATUS_PENDING_APPROVAL,
                PaymentVoucher::STATUS_APPROVED,
            ])
            ->get();

        foreach ($vouchers as $voucher) {
            $obligations[] = [
                'type' => 'pending_voucher',
                'description' => "Voucher {$voucher->voucher_number} - {$voucher->status}",
                'amount' => $voucher->amount_requested,
            ];
        }

        return $obligations;
    }

    public function generateClosureSummary(PurchaseRequisition $requisition): array
    {
        $po = $requisition->purchaseOrder;

        return [
            'requisition_number' => $requisition->requisition_number,
            'requisition_status' => $requisition->status,
            'po_number' => $po?->purchase_order_number,
            'po_status' => $po?->status,
            'total_po_value' => $po?->total_amount,
            'total_invoiced' => SupplierInvoice::where('purchase_order_id', $po?->id)->sum('total_amount'),
            'total_paid' => SupplierInvoice::where('purchase_order_id', $po?->id)->sum('paid_amount'),
            'grn_count' => GoodsReceiptNote::where('purchase_order_id', $po?->id)->count(),
            'invoice_count' => SupplierInvoice::where('purchase_order_id', $po?->id)->count(),
        ];
    }

    public function createDraft(PurchaseRequisition $requisition, User $actor, array $data): ProcurementClosure
    {
        if (ProcurementClosure::where('purchase_requisition_id', $requisition->id)->exists()) {
            throw new RuntimeException('A closure already exists for this requisition.');
        }

        return DB::transaction(function () use ($requisition, $actor, $data) {
            $closure = ProcurementClosure::create([
                'purchase_requisition_id' => $requisition->id,
                'purchase_order_id' => $requisition->purchaseOrder?->id,
                'closure_status' => ProcurementClosure::STATUS_DRAFT,
                'completion_summary' => $data['completion_summary'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->recordApproval($closure, ProcurementClosureApproval::ACTION_CREATED, $actor);

            return $closure;
        });
    }

    public function updateDraft(ProcurementClosure $closure, User $actor, array $data): ProcurementClosure
    {
        if (! $closure->isEditable()) {
            throw new RuntimeException('Only draft closures can be updated.');
        }

        return DB::transaction(function () use ($closure, $data) {
            $closure->update([
                'completion_summary' => $data['completion_summary'] ?? $closure->completion_summary,
                'notes' => $data['notes'] ?? $closure->notes,
            ]);

            return $closure;
        });
    }

    public function submitForRequesterConfirmation(ProcurementClosure $closure, User $actor): ProcurementClosure
    {
        if (! $closure->canBeSubmitted()) {
            throw new RuntimeException('Only draft closures can be submitted for confirmation.');
        }

        $validation = $this->validateClosureEligibility($closure->purchaseRequisition);
        if (! $validation['eligible'] && ! $validation['can_close_as_cancelled']) {
            throw new RuntimeException('Closure is not eligible: '.implode(', ', $validation['issues']));
        }

        return DB::transaction(function () use ($closure, $actor) {
            $closure->closure_status = ProcurementClosure::STATUS_PENDING_REQUESTER_CONFIRMATION;
            $closure->save();

            $this->recordApproval($closure, ProcurementClosureApproval::ACTION_SUBMITTED_FOR_CONFIRMATION, $actor);

            return $closure;
        });
    }

    public function requesterConfirm(ProcurementClosure $closure, User $actor, ?string $comments = null): ProcurementClosure
    {
        if (! $closure->isPendingRequesterConfirmation()) {
            throw new RuntimeException('Closure is not pending requester confirmation.');
        }

        if ($closure->purchaseRequisition->requester_id !== $actor->id && ! $actor->hasAnyRole(['super_admin', 'ceo'])) {
            throw new RuntimeException('Only the original requester can confirm closure.');
        }

        return DB::transaction(function () use ($closure, $actor, $comments) {
            $closure->closure_status = ProcurementClosure::STATUS_CONFIRMED;
            $closure->requester_confirmed_by = $actor->id;
            $closure->requester_confirmed_at = now();
            $closure->save();

            $this->recordApproval($closure, ProcurementClosureApproval::ACTION_REQUESTER_CONFIRMED, $actor, $comments);

            return $closure;
        });
    }

    public function returnForResolution(ProcurementClosure $closure, User $actor, string $reason): ProcurementClosure
    {
        if (! $closure->isPendingRequesterConfirmation()) {
            throw new RuntimeException('Closure is not pending requester confirmation.');
        }

        return DB::transaction(function () use ($closure, $actor, $reason) {
            $closure->closure_status = ProcurementClosure::STATUS_DRAFT;
            $closure->save();

            $this->recordApproval($closure, ProcurementClosureApproval::ACTION_CANCELLED, $actor, $reason);

            return $closure;
        });
    }

    public function close(ProcurementClosure $closure, User $actor): ProcurementClosure
    {
        if (! $closure->canBeClosed()) {
            throw new RuntimeException('Closure must be confirmed before closing.');
        }

        if ($closure->requester_confirmed_by === $actor->id && ! $actor->hasAnyRole(['super_admin', 'ceo'])) {
            throw new RuntimeException('Requester cannot perform final closure.');
        }

        return DB::transaction(function () use ($closure, $actor) {
            $closure->closure_status = ProcurementClosure::STATUS_CONFIRMED;
            $closure->closed_by = $actor->id;
            $closure->closed_at = now();
            $closure->save();

            $this->recordApproval($closure, ProcurementClosureApproval::ACTION_CLOSED, $actor);

            if ($closure->purchaseOrder) {
                $closure->purchaseOrder->lockForUpdate();
                $closure->purchaseOrder->status = PurchaseOrder::STATUS_CLOSED;
                $closure->purchaseOrder->save();
                $this->supplierPerformanceService->calculate($closure->purchaseOrder->supplier);
            }

            return $closure;
        });
    }

    public function closeWithException(ProcurementClosure $closure, User $actor, string $reason, ?string $comments = null): ProcurementClosure
    {
        if (! $actor->hasAnyRole(['super_admin', 'gm', 'ceo'])) {
            throw new RuntimeException('Only GM or Super Admin can close with exception.');
        }

        if (empty($reason)) {
            throw new RuntimeException('Exception reason is required.');
        }

        return DB::transaction(function () use ($closure, $actor, $reason, $comments) {
            $closure->closure_status = ProcurementClosure::STATUS_CLOSED_WITH_EXCEPTION;
            $closure->exception_reason = $reason;
            $closure->closed_by = $actor->id;
            $closure->closed_at = now();
            $closure->save();

            $this->recordApproval($closure, ProcurementClosureApproval::ACTION_CLOSED_WITH_EXCEPTION, $actor, $comments);

            if ($closure->purchaseOrder) {
                $closure->purchaseOrder->lockForUpdate();
                $closure->purchaseOrder->status = PurchaseOrder::STATUS_CLOSED;
                $closure->purchaseOrder->save();
                $this->supplierPerformanceService->calculate($closure->purchaseOrder->supplier);
            }

            return $closure;
        });
    }

    public function cancelDraft(ProcurementClosure $closure, User $actor, string $reason): ProcurementClosure
    {
        if (! $closure->canBeCancelled()) {
            throw new RuntimeException('Only draft or pending confirmation closures can be cancelled.');
        }

        return DB::transaction(function () use ($closure, $actor, $reason) {
            $closure->closure_status = ProcurementClosure::STATUS_CANCELLED;
            $closure->save();

            $this->recordApproval($closure, ProcurementClosureApproval::ACTION_CANCELLED, $actor, $reason);

            return $closure;
        });
    }
}
