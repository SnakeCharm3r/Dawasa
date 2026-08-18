<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TenderResponse extends Model
{
    protected $guarded = [];

    protected $casts = [
        'quotation_date' => 'date', 'valid_until' => 'date', 'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime', 'award_notified_at' => 'datetime', 'subtotal' => 'decimal:2', 'tax_amount' => 'decimal:2', 'total_amount' => 'decimal:2',
    ];

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TenderResponseItem::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(TenderResponseDocument::class);
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(SupplierQuotation::class, 'supplier_quotation_id');
    }
}
