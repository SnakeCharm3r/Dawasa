<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderApproval extends Model
{
    use HasFactory;

    public const ACTION_CREATED = 'created';

    public const ACTION_SUBMITTED_FOR_CONFIRMATION = 'submitted_for_confirmation';

    public const ACTION_ACCOUNTANT_CONFIRMED = 'accountant_confirmed';

    public const ACTION_ACCOUNTANT_REJECTED = 'accountant_rejected';

    public const ACTION_ISSUED = 'issued';

    public const ACTION_ACKNOWLEDGED = 'acknowledged';

    public const ACTION_CANCELLED = 'cancelled';

    protected $fillable = [
        'purchase_order_id',
        'action',
        'actor_id',
        'comments',
        'action_at',
    ];

    protected $casts = [
        'action_at' => 'datetime',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
