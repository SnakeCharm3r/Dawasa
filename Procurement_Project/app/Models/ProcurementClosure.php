<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcurementClosure extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING_REQUESTER_CONFIRMATION = 'pending_requester_confirmation';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CLOSED_WITH_EXCEPTION = 'closed_with_exception';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'purchase_requisition_id',
        'purchase_order_id',
        'closure_status',
        'completion_summary',
        'requester_confirmed_by',
        'requester_confirmed_at',
        'closed_by',
        'closed_at',
        'exception_reason',
        'notes',
    ];

    protected $casts = [
        'requester_confirmed_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function purchaseRequisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function requesterConfirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_confirmed_by');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(ProcurementClosureApproval::class);
    }

    public function isEditable(): bool
    {
        return $this->closure_status === self::STATUS_DRAFT;
    }

    public function canBeSubmitted(): bool
    {
        return $this->closure_status === self::STATUS_DRAFT;
    }

    public function isPendingRequesterConfirmation(): bool
    {
        return $this->closure_status === self::STATUS_PENDING_REQUESTER_CONFIRMATION;
    }

    public function isConfirmed(): bool
    {
        return $this->closure_status === self::STATUS_CONFIRMED;
    }

    public function canBeClosed(): bool
    {
        return $this->closure_status === self::STATUS_CONFIRMED;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->closure_status, [
            self::STATUS_DRAFT,
            self::STATUS_PENDING_REQUESTER_CONFIRMATION,
        ], true);
    }
}
