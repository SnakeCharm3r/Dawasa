<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierInvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_invoice_id',
        'purchase_order_item_id',
        'goods_receipt_note_item_id',
        'item_name',
        'specification',
        'quantity_invoiced',
        'quantity_previously_invoiced',
        'quantity_accepted',
        'unit',
        'unit_price',
        'line_total',
    ];

    protected $casts = [
        'quantity_invoiced' => 'decimal:2',
        'quantity_previously_invoiced' => 'decimal:2',
        'quantity_accepted' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function supplierInvoice(): BelongsTo
    {
        return $this->belongsTo(SupplierInvoice::class);
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function goodsReceiptNoteItem(): BelongsTo
    {
        return $this->belongsTo(GoodsReceiptNoteItem::class);
    }
}
