<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceiptApproval extends Model
{
    use HasFactory;

    public const ACTION_CREATED = 'created';
    public const ACTION_SUBMITTED = 'submitted';
    public const ACTION_INSPECTED = 'inspected';
    public const ACTION_PARTIALLY_ACCEPTED = 'partially_accepted';
    public const ACTION_ACCEPTED = 'accepted';
    public const ACTION_REJECTED = 'rejected';
    public const ACTION_CANCELLED = 'cancelled';

    protected $fillable = [
        'goods_receipt_note_id',
        'action',
        'actor_id',
        'comments',
        'action_at',
    ];

    protected $casts = [
        'action_at' => 'datetime',
    ];

    public function goodsReceiptNote(): BelongsTo
    {
        return $this->belongsTo(GoodsReceiptNote::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
