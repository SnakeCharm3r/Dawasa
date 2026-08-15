<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentVoucher extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_PENDING_APPROVAL = 'pending_approval';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_RETURNED = 'returned';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'voucher_number',
        'supplier_invoice_id',
        'supplier_id',
        'business_entity_id',
        'financial_year_id',
        'payment_date',
        'payment_method',
        'payment_reference',
        'amount_requested',
        'amount_approved',
        'amount_paid',
        'status',
        'prepared_by',
        'submitted_at',
        'approved_by',
        'approved_at',
        'paid_by',
        'paid_at',
        'comments',
        'cancellation_reason',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount_requested' => 'decimal:2',
        'amount_approved' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function supplierInvoice(): BelongsTo
    {
        return $this->belongsTo(SupplierInvoice::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function businessEntity(): BelongsTo
    {
        return $this->belongsTo(BusinessEntity::class);
    }

    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class);
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(PaymentApproval::class);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_RETURNED,
        ], true);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_RETURNED,
            self::STATUS_REJECTED,
        ], true);
    }

    public function preparerId(): ?int
    {
        return $this->approvals()->where('action', 'created')->value('actor_id');
    }
}
