<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenderResponseItem extends Model
{
    protected $guarded = [];
    protected $casts = ['unit_price' => 'decimal:2', 'line_total' => 'decimal:2'];
    public function response(): BelongsTo { return $this->belongsTo(TenderResponse::class, 'tender_response_id'); }
    public function tenderItem(): BelongsTo { return $this->belongsTo(TenderItem::class); }
}
