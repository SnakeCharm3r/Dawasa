<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceiptNoteItem extends Model
{
    use HasFactory;

    public const CONDITION_PENDING = 'pending';
    public const CONDITION_ACCEPTED = 'accepted';
    public const CONDITION_PARTIALLY_ACCEPTED = 'partially_accepted';
    public const CONDITION_REJECTED = 'rejected';
    public const CONDITION_DAMAGED = 'damaged';

    protected $fillable = [
        'goods_receipt_note_id',
        'purchase_order_item_id',
        'item_name',
        'specification',
        'quantity_ordered',
        'quantity_previously_received',
        'quantity_received',
        'quantity_accepted',
        'quantity_rejected',
        'unit',
        'condition_status',
        'rejection_reason',
        'inspection_notes',
    ];

    protected $casts = [
        'quantity_ordered' => 'decimal:2',
        'quantity_previously_received' => 'decimal:2',
        'quantity_received' => 'decimal:2',
        'quantity_accepted' => 'decimal:2',
        'quantity_rejected' => 'decimal:2',
    ];

    public function goodsReceiptNote(): BelongsTo
    {
        return $this->belongsTo(GoodsReceiptNote::class);
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }
}
