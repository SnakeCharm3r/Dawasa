<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceMatchRecord extends Model
{
    use HasFactory;

    public const MATCH_STATUS_MATCHED = 'matched';
    public const MATCH_STATUS_QUANTITY_VARIANCE = 'quantity_variance';
    public const MATCH_STATUS_PRICE_VARIANCE = 'price_variance';
    public const MATCH_STATUS_MISSING_GRN = 'missing_grn';
    public const MATCH_STATUS_FAILED = 'failed';

    protected $fillable = [
        'supplier_invoice_id',
        'purchase_order_id',
        'goods_receipt_note_id',
        'match_status',
        'po_amount',
        'grn_accepted_amount',
        'invoice_amount',
        'variance_amount',
        'variance_reason',
        'matched_by',
        'matched_at',
    ];

    protected $casts = [
        'po_amount' => 'decimal:2',
        'grn_accepted_amount' => 'decimal:2',
        'invoice_amount' => 'decimal:2',
        'variance_amount' => 'decimal:2',
        'matched_at' => 'datetime',
    ];

    public function supplierInvoice(): BelongsTo
    {
        return $this->belongsTo(SupplierInvoice::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function goodsReceiptNote(): BelongsTo
    {
        return $this->belongsTo(GoodsReceiptNote::class);
    }

    public function matchedBy(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
