<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsReceiptNote extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_INSPECTED = 'inspected';
    public const STATUS_PARTIALLY_ACCEPTED = 'partially_accepted';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'grn_number',
        'purchase_order_id',
        'supplier_id',
        'business_entity_id',
        'received_by',
        'received_date',
        'delivery_note_number',
        'supplier_invoice_reference',
        'delivery_condition',
        'status',
        'inspection_required',
        'inspected_by',
        'inspected_at',
        'inspection_comments',
        'received_location',
        'notes',
        'submitted_at',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'received_date' => 'date',
        'inspection_required' => 'boolean',
        'inspected_at' => 'datetime',
        'submitted_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function businessEntity(): BelongsTo
    {
        return $this->belongsTo(BusinessEntity::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function inspectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptNoteItem::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(GoodsReceiptApproval::class);
    }

    public function isEditable(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function canBeCancelled(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }
}
