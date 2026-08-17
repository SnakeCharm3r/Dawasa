<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPerformanceEvaluation extends Model
{
    protected $guarded = [];

    protected $casts = [
        'evaluation_period_start' => 'date',
        'evaluation_period_end' => 'date',
        'total_awarded_value' => 'decimal:2',
        'delivery_score' => 'decimal:2',
        'quality_score' => 'decimal:2',
        'compliance_score' => 'decimal:2',
        'responsiveness_score' => 'decimal:2',
        'commercial_reliability_score' => 'decimal:2',
        'overall_score' => 'decimal:2',
        'calculated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Supplier performance evaluations are immutable.'));
        static::deleting(fn () => throw new \LogicException('Supplier performance evaluations are immutable.'));
    }

    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function businessEntity(): BelongsTo { return $this->belongsTo(BusinessEntity::class); }
    public function calculatedBy(): BelongsTo { return $this->belongsTo(User::class, 'calculated_by'); }
}
