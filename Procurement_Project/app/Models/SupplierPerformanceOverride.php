<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPerformanceOverride extends Model
{
    protected $guarded = [];

    protected $casts = ['expires_at' => 'datetime'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Supplier performance overrides are immutable.'));
        static::deleting(fn () => throw new \LogicException('Supplier performance overrides are immutable.'));
    }

    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
