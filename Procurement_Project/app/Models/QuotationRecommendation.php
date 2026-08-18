<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuotationRecommendation extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_RETURNED = 'returned';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_WITHDRAWN = 'withdrawn';

    protected $fillable = [
        'purchase_requisition_id',
        'selected_quotation_id',
        'recommended_by',
        'recommended_at',
        'reason_for_selection',
        'non_lowest_price_reason',
        'total_quoted_amount',
        'status',
    ];

    protected $casts = [
        'recommended_at' => 'datetime',
        'total_quoted_amount' => 'decimal:2',
    ];

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class, 'purchase_requisition_id');
    }

    public function selectedQuotation(): BelongsTo
    {
        return $this->belongsTo(SupplierQuotation::class, 'selected_quotation_id');
    }

    public function recommendedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recommended_by');
    }

    public function procurementApprovals(): HasMany
    {
        return $this->hasMany(ProcurementApproval::class, 'quotation_recommendation_id');
    }
}
