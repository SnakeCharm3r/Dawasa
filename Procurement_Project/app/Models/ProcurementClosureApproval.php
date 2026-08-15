<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementClosureApproval extends Model
{
    use HasFactory;

    public const ACTION_CREATED = 'created';
    public const ACTION_SUBMITTED_FOR_CONFIRMATION = 'submitted_for_confirmation';
    public const ACTION_REQUESTER_CONFIRMED = 'requester_confirmed';
    public const ACTION_CLOSED = 'closed';
    public const ACTION_CLOSED_WITH_EXCEPTION = 'closed_with_exception';
    public const ACTION_CANCELLED = 'cancelled';

    protected $fillable = [
        'procurement_closure_id',
        'action',
        'actor_id',
        'comments',
        'action_at',
    ];

    protected $casts = [
        'action_at' => 'datetime',
    ];

    public function procurementClosure(): BelongsTo
    {
        return $this->belongsTo(ProcurementClosure::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
