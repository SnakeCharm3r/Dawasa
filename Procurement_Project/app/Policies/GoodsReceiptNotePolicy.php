<?php

namespace App\Policies;

use App\Models\GoodsReceiptNote;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GoodsReceiptNotePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'auditor', 'accountant', 'procurement_officer', 'gm', 'department_head', 'requester']);
    }

    public function view(User $user, GoodsReceiptNote $goodsReceiptNote): bool
    {
        if ($user->hasAnyRole(['super_admin', 'auditor', 'accountant', 'procurement_officer', 'gm'])) {
            return true;
        }

        if ($user->hasRole('department_head')) {
            return $goodsReceiptNote->purchaseOrder->requisition->department_id === $user->department_id;
        }

        if ($user->hasRole('requester')) {
            return $goodsReceiptNote->purchaseOrder->requisition->requester_id === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'procurement_officer', 'storekeeper', 'receiving_officer']);
    }

    public function update(User $user, GoodsReceiptNote $goodsReceiptNote): bool
    {
        if (! $goodsReceiptNote->isEditable()) {
            return false;
        }

        return $user->hasAnyRole(['super_admin', 'procurement_officer', 'storekeeper', 'receiving_officer']);
    }

    public function delete(User $user, GoodsReceiptNote $goodsReceiptNote): bool
    {
        return false;
    }

    public function restore(User $user, GoodsReceiptNote $goodsReceiptNote): bool
    {
        return $user->hasRole('super_admin');
    }

    public function forceDelete(User $user, GoodsReceiptNote $goodsReceiptNote): bool
    {
        return false;
    }

    public function submit(User $user, GoodsReceiptNote $goodsReceiptNote): bool
    {
        if (! $goodsReceiptNote->isEditable()) {
            return false;
        }

        return $user->hasAnyRole(['super_admin', 'procurement_officer', 'storekeeper', 'receiving_officer']);
    }

    public function inspect(User $user, GoodsReceiptNote $goodsReceiptNote): bool
    {
        if ($goodsReceiptNote->status !== GoodsReceiptNote::STATUS_SUBMITTED) {
            return false;
        }

        if ($user->id === $goodsReceiptNote->received_by) {
            return false;
        }

        return $user->hasAnyRole(['super_admin', 'department_head']);
    }

    public function cancel(User $user, GoodsReceiptNote $goodsReceiptNote): bool
    {
        if (! $goodsReceiptNote->canBeCancelled()) {
            return false;
        }

        return $user->hasAnyRole(['super_admin', 'procurement_officer', 'storekeeper', 'receiving_officer']);
    }
}
