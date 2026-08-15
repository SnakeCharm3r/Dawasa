<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentApproval extends Model
{
    use HasFactory;

    public const ACTION_CREATED = 'created';
    public const ACTION_SUBMITTED = 'submitted';
    public const ACTION_APPROVED = 'approved';
    public const ACTION_RETURNED = 'returned';
    public const ACTION_REJECTED = 'rejected';
    public const ACTION_PAID = 'paid';
    public const ACTION_CANCELLED = 'cancelled';

    protected $fillable = [
        'payment_voucher_id',
        'action',
        'actor_id',
        'comments',
        'action_at',
    ];

    protected $casts = [
        'action_at' => 'datetime',
    ];

    public function paymentVoucher(): BelongsTo
    {
        return $this->belongsTo(PaymentVoucher::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
