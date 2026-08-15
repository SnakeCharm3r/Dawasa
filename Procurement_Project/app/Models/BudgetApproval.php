<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetApproval extends Model
{
    use HasFactory;

    public const ACTION_SUBMITTED = 'submitted';
    public const ACTION_APPROVED = 'approved';
    public const ACTION_RETURNED = 'returned';
    public const ACTION_REJECTED = 'rejected';
    public const ACTION_REVISED = 'revised';

    protected $fillable = [
        'entity_budget_id',
        'action',
        'actor_id',
        'comments',
        'action_at',
    ];

    protected $casts = [
        'action_at' => 'datetime',
    ];

    public function budget(): BelongsTo
    {
        return $this->belongsTo(EntityBudget::class, 'entity_budget_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
