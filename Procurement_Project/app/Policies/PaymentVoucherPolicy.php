<?php

namespace App\Policies;

use App\Models\PaymentVoucher;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PaymentVoucherPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'auditor', 'accountant', 'gm']);
    }

    public function view(User $user, PaymentVoucher $paymentVoucher): bool
    {
        if ($user->hasAnyRole(['super_admin', 'auditor', 'accountant', 'gm'])) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'accountant']);
    }

    public function update(User $user, PaymentVoucher $paymentVoucher): bool
    {
        if (! $paymentVoucher->isEditable()) {
            return false;
        }

        return $user->hasAnyRole(['super_admin', 'accountant']);
    }

    public function delete(User $user, PaymentVoucher $paymentVoucher): bool
    {
        return false;
    }

    public function restore(User $user, PaymentVoucher $paymentVoucher): bool
    {
        return $user->hasRole('super_admin');
    }

    public function forceDelete(User $user, PaymentVoucher $paymentVoucher): bool
    {
        return false;
    }

    public function submit(User $user, PaymentVoucher $paymentVoucher): bool
    {
        if (! $paymentVoucher->isEditable()) {
            return false;
        }

        return $user->hasAnyRole(['super_admin', 'accountant']);
    }

    public function approve(User $user, PaymentVoucher $paymentVoucher): bool
    {
        if ($paymentVoucher->status !== PaymentVoucher::STATUS_SUBMITTED) {
            return false;
        }

        $preparerId = $paymentVoucher->preparerId();
        if ($preparerId && $preparerId === $user->id) {
            return false;
        }

        return $user->hasAnyRole(['super_admin', 'gm']);
    }

    public function return(User $user, PaymentVoucher $paymentVoucher): bool
    {
        if (! in_array($paymentVoucher->status, [
            PaymentVoucher::STATUS_SUBMITTED,
            PaymentVoucher::STATUS_PENDING_APPROVAL,
        ], true)) {
            return false;
        }

        return $user->hasAnyRole(['super_admin', 'gm']);
    }

    public function reject(User $user, PaymentVoucher $paymentVoucher): bool
    {
        if (! in_array($paymentVoucher->status, [
            PaymentVoucher::STATUS_SUBMITTED,
            PaymentVoucher::STATUS_PENDING_APPROVAL,
        ], true)) {
            return false;
        }

        return $user->hasAnyRole(['super_admin', 'gm']);
    }

    public function recordPayment(User $user, PaymentVoucher $paymentVoucher): bool
    {
        if ($paymentVoucher->status !== PaymentVoucher::STATUS_APPROVED) {
            return false;
        }

        return $user->hasAnyRole(['super_admin', 'accountant']);
    }

    public function cancel(User $user, PaymentVoucher $paymentVoucher): bool
    {
        if (! $paymentVoucher->canBeCancelled()) {
            return false;
        }

        return $user->hasAnyRole(['super_admin', 'accountant']);
    }
}
