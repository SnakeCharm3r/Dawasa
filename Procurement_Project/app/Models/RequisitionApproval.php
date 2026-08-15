<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequisitionApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_requisition_id',
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

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
