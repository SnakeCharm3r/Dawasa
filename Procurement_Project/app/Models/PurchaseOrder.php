<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING_ACCOUNTANT_CONFIRMATION = 'pending_accountant_confirmation';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_ISSUED = 'issued';

    public const STATUS_ACKNOWLEDGED = 'acknowledged';

    public const STATUS_PARTIALLY_RECEIVED = 'partially_received';

    public const STATUS_FULLY_RECEIVED = 'fully_received';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'purchase_order_number',
        'purchase_requisition_id',
        'supplier_id',
        'quotation_recommendation_id',
        'selected_quotation_id',
        'business_entity_id',
        'financial_year_id',
        'status',
        'order_date',
        'expected_delivery_date',
        'currency',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'payment_terms',
        'delivery_terms',
        'delivery_address',
        'notes',
        'accountant_confirmed_by',
        'accountant_confirmed_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'issued_by',
        'issued_at',
        'supplier_acknowledged_at',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'accountant_confirmed_at' => 'datetime',
        'rejected_at' => 'datetime',
        'issued_at' => 'datetime',
        'supplier_acknowledged_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class, 'purchase_requisition_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function quotationRecommendation(): BelongsTo
    {
        return $this->belongsTo(QuotationRecommendation::class);
    }

    public function selectedQuotation(): BelongsTo
    {
        return $this->belongsTo(SupplierQuotation::class, 'selected_quotation_id');
    }

    public function businessEntity(): BelongsTo
    {
        return $this->belongsTo(BusinessEntity::class);
    }

    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(PurchaseOrderApproval::class);
    }

    public function accountantConfirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accountant_confirmed_by');
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function preparerId(): ?int
    {
        return $this->approvals()->where('action', PurchaseOrderApproval::ACTION_CREATED)->value('actor_id');
    }

    public function isEditable(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_PENDING_ACCOUNTANT_CONFIRMATION,
            self::STATUS_CONFIRMED,
            self::STATUS_ISSUED,
        ], true);
    }

    public function calculateTotal(): string
    {
        $subtotal = (float) ($this->subtotal ?? 0);
        $discount = (float) ($this->discount_amount ?? 0);
        $tax = (float) ($this->tax_amount ?? 0);

        return number_format($subtotal - $discount + $tax, 2, '.', '');
    }
}
