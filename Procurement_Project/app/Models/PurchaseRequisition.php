<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PurchaseRequisition extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_RETURNED = 'returned';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_APPROVED_FOR_SOURCING = 'approved_for_sourcing';

    public const STATUS_QUOTATIONS_READY = 'quotations_ready';

    public const STATUS_PENDING_FINAL_APPROVAL = 'pending_final_approval';

    public const STATUS_APPROVED_FOR_PURCHASE = 'approved_for_purchase';

    public const STATUS_RETURNED_TO_SOURCING = 'returned_to_sourcing';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'requisition_number',
        'business_entity_id',
        'department_id',
        'requester_id',
        'line_manager_id',
        'required_date',
        'purpose',
        'estimated_amount',
        'committed_amount',
        'status',
        'submitted_at',
        'approved_at',
        'returned_at',
        'rejected_at',
        'cancelled_at',
        'estimate_difference_reason',
    ];

    protected $casts = [
        'required_date' => 'date',
        'estimated_amount' => 'decimal:2',
        'committed_amount' => 'decimal:2',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'returned_at' => 'datetime',
        'rejected_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function businessEntity(): BelongsTo
    {
        return $this->belongsTo(BusinessEntity::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function lineManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'line_manager_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseRequisitionItem::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(PurchaseRequisitionAttachment::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(RequisitionApproval::class, 'purchase_requisition_id');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'subject_id')
            ->where('subject_type', self::class)
            ->latest();
    }

    public function supplierQuotations(): HasMany
    {
        return $this->hasMany(SupplierQuotation::class, 'purchase_requisition_id');
    }

    public function quotationRecommendations(): HasMany
    {
        return $this->hasMany(QuotationRecommendation::class, 'purchase_requisition_id');
    }

    public function purchaseOrder(): HasOne
    {
        return $this->hasOne(PurchaseOrder::class, 'purchase_requisition_id');
    }

    public function getEstimatedAmountAttribute($value)
    {
        return $value !== null ? number_format($value, 2, '.', '') : null;
    }

    public function calculateTotalEstimate(): string
    {
        $total = $this->items->sum(function ($item) {
            return $item->estimated_total ?? 0;
        });

        return number_format($total, 2, '.', '');
    }
}
