<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementApproval extends Model
{
    use HasFactory;

    public const ACTION_RECOMMENDATION_SUBMITTED = 'recommendation_submitted';
    public const ACTION_APPROVED = 'approved';
    public const ACTION_RETURNED_TO_SOURCING = 'returned_to_sourcing';
    public const ACTION_REJECTED = 'rejected';

    protected $fillable = [
        'purchase_requisition_id',
        'quotation_recommendation_id',
        'action',
        'actor_id',
        'comments',
        'action_at',
    ];

    protected $casts = [
        'action_at' => 'datetime',
    ];

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class, 'purchase_requisition_id');
    }

    public function recommendation(): BelongsTo
    {
        return $this->belongsTo(QuotationRecommendation::class, 'quotation_recommendation_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
