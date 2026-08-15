<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetTransaction extends Model
{
    use HasFactory;

    public const TYPE_COMMITMENT = 'commitment';
    public const TYPE_COMMITMENT_RELEASE = 'commitment_release';
    public const TYPE_EXPENDITURE = 'expenditure';
    public const TYPE_EXPENDITURE_REVERSAL = 'expenditure_reversal';
    public const TYPE_ADJUSTMENT = 'adjustment';

    protected $fillable = [
        'entity_budget_id',
        'transaction_type',
        'amount',
        'reference_type',
        'reference_id',
        'description',
        'created_by',
        'transaction_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'datetime',
    ];

    public function budget(): BelongsTo
    {
        return $this->belongsTo(EntityBudget::class, 'entity_budget_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
