<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\BudgetTransaction;
use App\Models\EntityBudget;
use App\Models\PaymentApproval;
use App\Models\PaymentVoucher;
use App\Models\SupplierInvoice;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function generateVoucherNumber(PaymentVoucher $voucher): string
    {
        $year = $voucher->payment_date ? $voucher->payment_date->year : now()->year;
        $count = PaymentVoucher::whereYear('payment_date', $year)
            ->whereNotNull('voucher_number')
            ->count();

        return 'PV-'.$year.'-'.str_pad($count + 1, 6, '0', STR_PAD_LEFT);
    }

    public function createVoucherFromInvoice(SupplierInvoice $invoice, User $actor, array $data): PaymentVoucher
    {
        return DB::transaction(function () use ($invoice, $actor, $data) {
            $invoice = SupplierInvoice::lockForUpdate()->findOrFail($invoice->id);

            if (! in_array($invoice->status, [
                SupplierInvoice::STATUS_MATCHED,
                SupplierInvoice::STATUS_APPROVED_FOR_PAYMENT,
            ], true)) {
                throw new \RuntimeException('Payment voucher can only be created from matched or approved invoices.');
            }

            if ((float) $data['amount_requested'] > $this->availableVoucherAmount($invoice)) {
                throw new \RuntimeException('Requested amount cannot exceed the unallocated invoice outstanding amount.');
            }

            $voucher = PaymentVoucher::create([
                'supplier_invoice_id' => $invoice->id,
                'supplier_id' => $invoice->supplier_id,
                'business_entity_id' => $invoice->business_entity_id,
                'financial_year_id' => $invoice->financial_year_id,
                'payment_date' => $data['payment_date'] ?? null,
                'payment_method' => $data['payment_method'],
                'payment_reference' => $data['payment_reference'] ?? null,
                'amount_requested' => $data['amount_requested'],
                'amount_approved' => null,
                'amount_paid' => 0,
                'status' => PaymentVoucher::STATUS_DRAFT,
                'prepared_by' => $actor->id,
                'comments' => $data['comments'] ?? null,
            ]);

            $voucher->voucher_number = $this->generateVoucherNumber($voucher);
            $voucher->save();

            PaymentApproval::create([
                'payment_voucher_id' => $voucher->id,
                'action' => PaymentApproval::ACTION_CREATED,
                'actor_id' => $actor->id,
                'comments' => null,
                'action_at' => now(),
            ]);

            ActivityLog::record(
                $actor,
                'payment_voucher.created',
                $voucher,
                [],
                ['status' => PaymentVoucher::STATUS_DRAFT, 'amount_requested' => $voucher->amount_requested]
            );

            return $voucher->fresh();
        });
    }

    public function updateVoucher(PaymentVoucher $voucher, array $data): PaymentVoucher
    {
        if (! $voucher->isEditable()) {
            throw new \RuntimeException('Only draft or returned vouchers can be updated.');
        }

        return DB::transaction(function () use ($voucher, $data) {
            $invoice = SupplierInvoice::lockForUpdate()->findOrFail($voucher->supplier_invoice_id);
            $amount = (float) ($data['amount_requested'] ?? $voucher->amount_requested);

            if ($amount > $this->availableVoucherAmount($invoice, $voucher->id)) {
                throw new \RuntimeException('Requested amount cannot exceed the unallocated invoice outstanding amount.');
            }

            $voucher->update($data);

            return $voucher->fresh();
        });
    }

    public function submitVoucher(PaymentVoucher $voucher, User $actor): PaymentVoucher
    {
        if ($voucher->status !== PaymentVoucher::STATUS_DRAFT) {
            throw new \RuntimeException('Only draft vouchers can be submitted.');
        }

        return DB::transaction(function () use ($voucher, $actor) {
            $voucher->status = PaymentVoucher::STATUS_SUBMITTED;
            $voucher->submitted_at = now();
            $voucher->save();

            PaymentApproval::create([
                'payment_voucher_id' => $voucher->id,
                'action' => PaymentApproval::ACTION_SUBMITTED,
                'actor_id' => $actor->id,
                'comments' => null,
                'action_at' => now(),
            ]);

            ActivityLog::record(
                $actor,
                'payment_voucher.submitted',
                $voucher,
                ['status' => PaymentVoucher::STATUS_DRAFT],
                ['status' => PaymentVoucher::STATUS_SUBMITTED]
            );

            return $voucher->fresh();
        });
    }

    public function approveVoucher(PaymentVoucher $voucher, User $actor, ?string $comments = null): PaymentVoucher
    {
        if ($voucher->status !== PaymentVoucher::STATUS_SUBMITTED) {
            throw new \RuntimeException('Only submitted vouchers can be approved.');
        }

        $preparerId = $voucher->preparerId();
        if ($preparerId && $preparerId === $actor->id) {
            throw new \RuntimeException('GM cannot approve their own prepared voucher.');
        }

        return DB::transaction(function () use ($voucher, $actor, $comments) {
            $voucher->status = PaymentVoucher::STATUS_APPROVED;
            $voucher->amount_approved = $voucher->amount_requested;
            $voucher->approved_by = $actor->id;
            $voucher->approved_at = now();
            $voucher->comments = $comments;
            $voucher->save();

            PaymentApproval::create([
                'payment_voucher_id' => $voucher->id,
                'action' => PaymentApproval::ACTION_APPROVED,
                'actor_id' => $actor->id,
                'comments' => $comments,
                'action_at' => now(),
            ]);

            ActivityLog::record(
                $actor,
                'payment_voucher.approved',
                $voucher,
                ['status' => PaymentVoucher::STATUS_SUBMITTED],
                ['status' => PaymentVoucher::STATUS_APPROVED, 'approved_by' => $actor->id]
            );

            return $voucher->fresh();
        });
    }

    public function returnVoucher(PaymentVoucher $voucher, User $actor, string $reason): PaymentVoucher
    {
        if (! in_array($voucher->status, [
            PaymentVoucher::STATUS_SUBMITTED,
            PaymentVoucher::STATUS_PENDING_APPROVAL,
        ], true)) {
            throw new \RuntimeException('Only submitted or pending approval vouchers can be returned.');
        }

        return DB::transaction(function () use ($voucher, $actor, $reason) {
            $voucher->status = PaymentVoucher::STATUS_RETURNED;
            $voucher->comments = $reason;
            $voucher->save();

            PaymentApproval::create([
                'payment_voucher_id' => $voucher->id,
                'action' => PaymentApproval::ACTION_RETURNED,
                'actor_id' => $actor->id,
                'comments' => $reason,
                'action_at' => now(),
            ]);

            ActivityLog::record(
                $actor,
                'payment_voucher.returned',
                $voucher,
                ['status' => $voucher->getOriginal('status')],
                ['status' => PaymentVoucher::STATUS_RETURNED, 'reason' => $reason]
            );

            return $voucher->fresh();
        });
    }

    public function rejectVoucher(PaymentVoucher $voucher, User $actor, string $reason): PaymentVoucher
    {
        if (! in_array($voucher->status, [
            PaymentVoucher::STATUS_SUBMITTED,
            PaymentVoucher::STATUS_PENDING_APPROVAL,
        ], true)) {
            throw new \RuntimeException('Only submitted or pending approval vouchers can be rejected.');
        }

        return DB::transaction(function () use ($voucher, $actor, $reason) {
            $voucher->status = PaymentVoucher::STATUS_REJECTED;
            $voucher->comments = $reason;
            $voucher->save();

            PaymentApproval::create([
                'payment_voucher_id' => $voucher->id,
                'action' => PaymentApproval::ACTION_REJECTED,
                'actor_id' => $actor->id,
                'comments' => $reason,
                'action_at' => now(),
            ]);

            ActivityLog::record(
                $actor,
                'payment_voucher.rejected',
                $voucher,
                ['status' => $voucher->getOriginal('status')],
                ['status' => PaymentVoucher::STATUS_REJECTED, 'reason' => $reason]
            );

            return $voucher->fresh();
        });
    }

    public function recordPayment(PaymentVoucher $voucher, User $actor, array $data): PaymentVoucher
    {
        if ($voucher->status !== PaymentVoucher::STATUS_APPROVED) {
            throw new \RuntimeException('Only approved vouchers can have payments recorded.');
        }

        $paymentAmount = (float) $data['amount_paid'];
        if ($paymentAmount <= 0) {
            throw new \RuntimeException('Payment amount must be greater than zero.');
        }

        if ($paymentAmount > (float) $voucher->amount_approved) {
            throw new \RuntimeException('Payment amount cannot exceed approved amount.');
        }

        return DB::transaction(function () use ($voucher, $actor, $data, $paymentAmount) {
            $invoice = SupplierInvoice::lockForUpdate()->findOrFail($voucher->supplier_invoice_id);
            if ($paymentAmount > (float) $invoice->outstanding_amount) {
                throw new \RuntimeException('Payment amount cannot exceed the invoice outstanding amount.');
            }

            $budget = EntityBudget::query()
                ->lockForUpdate()
                ->where('business_entity_id', $invoice->business_entity_id)
                ->where('financial_year_id', $invoice->financial_year_id)
                ->where('status', EntityBudget::STATUS_APPROVED)
                ->firstOrFail();

            $voucher->payment_date = $data['payment_date'];
            $voucher->payment_reference = $data['payment_reference'];
            $voucher->amount_paid = $paymentAmount;
            $voucher->paid_by = $actor->id;
            $voucher->paid_at = now();
            $voucher->status = PaymentVoucher::STATUS_PAID;
            $voucher->save();

            PaymentApproval::create([
                'payment_voucher_id' => $voucher->id,
                'action' => PaymentApproval::ACTION_PAID,
                'actor_id' => $actor->id,
                'comments' => $data['payment_notes'] ?? null,
                'action_at' => now(),
            ]);

            $invoice->paid_amount += $paymentAmount;
            $invoice->updateOutstandingAmount();

            if ((float) $invoice->paid_amount >= (float) $invoice->total_amount) {
                $invoice->status = SupplierInvoice::STATUS_PAID;
            } else {
                $invoice->status = SupplierInvoice::STATUS_PARTIALLY_PAID;
            }
            $invoice->save();

            BudgetTransaction::create([
                'entity_budget_id' => $budget->id,
                'transaction_type' => 'expenditure',
                'amount' => $paymentAmount,
                'reference_type' => 'PaymentVoucher',
                'reference_id' => $voucher->id,
                'description' => "Payment for invoice {$invoice->invoice_number} via voucher {$voucher->voucher_number}",
            ]);

            $budget->spent_amount += $paymentAmount;
            $budget->committed_amount = max(0, (float) $budget->committed_amount - $paymentAmount);
            $budget->syncAvailable();

            ActivityLog::record(
                $actor,
                'payment.recorded',
                $voucher,
                ['status' => PaymentVoucher::STATUS_APPROVED],
                ['status' => PaymentVoucher::STATUS_PAID, 'amount_paid' => $paymentAmount]
            );

            ActivityLog::record(
                $actor,
                'invoice.payment_updated',
                $invoice,
                ['paid_amount' => $invoice->paid_amount - $paymentAmount],
                ['paid_amount' => $invoice->paid_amount, 'status' => $invoice->status]
            );

            return $voucher->fresh();
        });
    }

    private function availableVoucherAmount(SupplierInvoice $invoice, ?int $excludingVoucherId = null): float
    {
        $reserved = PaymentVoucher::query()
            ->where('supplier_invoice_id', $invoice->id)
            ->when($excludingVoucherId, fn ($query) => $query->where('id', '!=', $excludingVoucherId))
            ->whereIn('status', [
                PaymentVoucher::STATUS_DRAFT,
                PaymentVoucher::STATUS_SUBMITTED,
                PaymentVoucher::STATUS_PENDING_APPROVAL,
                PaymentVoucher::STATUS_APPROVED,
                PaymentVoucher::STATUS_RETURNED,
            ])
            ->sum('amount_requested');

        return max(0, (float) $invoice->outstanding_amount - (float) $reserved);
    }

    public function cancelVoucher(PaymentVoucher $voucher, User $actor, string $reason): PaymentVoucher
    {
        if (! $voucher->canBeCancelled()) {
            throw new \RuntimeException('This voucher cannot be cancelled.');
        }

        return DB::transaction(function () use ($voucher, $actor, $reason) {
            $voucher->status = PaymentVoucher::STATUS_CANCELLED;
            $voucher->cancellation_reason = $reason;
            $voucher->save();

            PaymentApproval::create([
                'payment_voucher_id' => $voucher->id,
                'action' => PaymentApproval::ACTION_CANCELLED,
                'actor_id' => $actor->id,
                'comments' => $reason,
                'action_at' => now(),
            ]);

            ActivityLog::record(
                $actor,
                'payment_voucher.cancelled',
                $voucher,
                ['status' => $voucher->getOriginal('status')],
                ['status' => PaymentVoucher::STATUS_CANCELLED, 'reason' => $reason]
            );

            return $voucher->fresh();
        });
    }
}
