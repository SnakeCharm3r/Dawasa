<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierQuotation extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_WITHDRAWN = 'withdrawn';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'purchase_requisition_id',
        'supplier_id',
        'prepared_by',
        'quotation_number',
        'valid_until',
        'total_amount',
        'status',
        'submitted_at',
        'withdrawn_at',
        'expired_at',
        'notes',
    ];

    protected $casts = [
        'valid_until' => 'date',
        'total_amount' => 'decimal:2',
        'submitted_at' => 'datetime',
        'withdrawn_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class, 'purchase_requisition_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function preparer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SupplierQuotationItem::class);
    }

    public function scopeValid($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where(function ($query) {
                $query->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', now()->toDateString());
            });
    }
}
